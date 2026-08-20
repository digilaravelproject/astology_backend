<?php

namespace App\Services;

use App\Models\CallSession;
use App\Models\IceCandidate;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\CallSessionRepository;
use App\Jobs\CallBillingTickJob;
use App\Jobs\CleanupMissedSessionJob;
use App\Services\PricingCalculatorService;
use App\Services\PresenceService;
use App\Services\WalletService;
use App\Services\BlockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NormalCallService
{
    protected $callRepo;
    protected $walletService;
    protected $presenceService;
    protected $pricingCalculator;
    protected $blockService;

    public function __construct(
        CallSessionRepository $callRepo,
        WalletService $walletService,
        PresenceService $presenceService,
        PricingCalculatorService $pricingCalculator,
        BlockService $blockService
    ) {
        $this->callRepo = $callRepo;
        $this->walletService = $walletService;
        $this->presenceService = $presenceService;
        $this->pricingCalculator = $pricingCalculator;
        $this->blockService = $blockService;
    }

    /**
     * Initiate a WebRTC call session with atomic checks.
     */
    public function initiateCall(int $consumerId, int $providerId): CallSession
    {
        return DB::transaction(function () use ($consumerId, $providerId) {
            // 1. Bidirectional block check
            if ($this->blockService->isBlockedBidirectional($consumerId, $providerId)) {
                throw new Exception("You cannot initiate a call with this user because of block status.");
            }

            // 2. Validate provider eligibility
            $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
            $astrologer = $provider->astrologer;
            if (!$astrologer || !$astrologer->is_call_enabled) {
                throw new Exception("Astrologer is not available for calls.");
            }

            // 3. Pricing rate calculation
            $pricing = $this->pricingCalculator->calculate($astrologer, 'call');
            $rate = (float) $pricing['customer_rate'];

            // 4. Provider busy state
            $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isCallBusy = CallSession::where('provider_id', $providerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            $hasWaitingQueue = CallSession::where('provider_id', $providerId)->where('status', 'waiting')->exists()
                || \App\Models\ChatSession::where('provider_id', $providerId)->where('status', 'waiting')->exists();

            $isBusy = $isChatBusy || $isCallBusy || $hasWaitingQueue;

            // 5. Consumer busy state
            $isConsumerChatBusy = \App\Models\ChatSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isConsumerCallBusy = CallSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            if ($isConsumerChatBusy || $isConsumerCallBusy) {
                throw new Exception("You are already in an active session.");
            }

            // 6. Consumer pending / waiting check
            $existingChatPending = \App\Models\ChatSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['initiated', 'waiting'])
                ->exists();
            $existingCallPending = CallSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['initiated', 'ringing', 'waiting'])
                ->exists();
            if ($existingChatPending || $existingCallPending) {
                throw new Exception("You already have a pending or waiting request.");
            }

            // 7. Check minimum balance (5 minutes minimum)
            $balance = $this->walletService->getBalance($consumerId);
            if ($balance < $rate * 5) {
                throw new Exception("Insufficient balance. You need minimum " . ($rate * 5) . " in your wallet to start this call.");
            }

            $status = $isBusy ? 'waiting' : 'initiated';

            /** @var CallSession $session */
            $session = $this->callRepo->create([
                'consumer_id'     => $consumerId,
                'provider_id'     => $providerId,
                'call_type'       => 'audio',
                'status'          => $status,
                'rate_per_minute' => $rate,
            ]);

            if ($status === 'initiated') {
                CleanupMissedSessionJob::dispatch($session->id, 'call')->delay(now()->addSeconds(60));
            }

            return $session;
        }, 3);
    }

    /**
     * Accept an initiated or waiting call session.
     */
    public function acceptCall(int $sessionId, int $providerId): CallSession
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            $session = CallSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->provider_id !== (int) $providerId || !in_array($session->status, ['initiated', 'ringing', 'waiting'])) {
                throw new Exception("The call session is no longer valid or has been cancelled.");
            }

            $provider = User::where('id', $providerId)->lockForUpdate()->first();

            $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isCallBusy = CallSession::where('provider_id', $providerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->where('id', '!=', $sessionId)
                ->exists();
            if ($isChatBusy || $isCallBusy) {
                throw new Exception("You are already in an active session.");
            }

            $this->callRepo->update($sessionId, [
                'status' => 'ongoing',
                'started_at' => now(),
                'last_billed_at' => now(),
            ]);

            $this->presenceService->setBusy($session->consumer_id, $sessionId);
            $this->presenceService->setBusy($providerId, $sessionId);

            CallBillingTickJob::dispatch($sessionId)->delay(now()->addMinute());

            $session->refresh();
            return $session;
        }, 3);
    }

    /**
     * End a call session and settle final billing.
     */
    public function endCall(int $sessionId, ?int $userId = null): CallSession
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $session = CallSession::where('id', $sessionId)->lockForUpdate()->first();
            if (!$session || !in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                return $session;
            }

            if ($userId && (int) $session->consumer_id !== (int) $userId && (int) $session->provider_id !== (int) $userId) {
                throw new Exception("You are not authorized to end this call.");
            }

            $consumerId = (int) $session->consumer_id;
            $providerId = (int) $session->provider_id;

            if ($consumerId < $providerId) {
                $consumerWallet = Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
                $providerWallet = Wallet::where('user_id', $providerId)->lockForUpdate()->first();
            } else {
                $providerWallet = Wallet::where('user_id', $providerId)->lockForUpdate()->first();
                $consumerWallet = Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
            }

            $endTime = now();
            $durationSeconds = $session->started_at ? (int) $session->started_at->diffInSeconds($endTime) : 0;
            $finalCost = (float) (ceil($durationSeconds / 60) * $session->rate_per_minute);

            $alreadyBilled = (float) ($session->total_cost ?? 0.00);
            $unbilledBalance = $finalCost - $alreadyBilled;

            $chargeAmount = 0.00;
            if ($unbilledBalance > 0 && $consumerWallet) {
                $chargeAmount = min($unbilledBalance, (float) $consumerWallet->balance);
                if ($chargeAmount > 0) {
                    $this->walletService->debitBalanceOnly($consumerId, $chargeAmount);

                    $provider = User::with('astrologer')->findOrFail($providerId);
                    $pricing = $this->pricingCalculator->calculate($provider->astrologer, 'call');
                    $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? 70.0);
                    $creditAmount = round(($chargeAmount * $astrologerSharePct) / 100, 2);

                    $this->walletService->creditBalanceOnly($providerId, $creditAmount);
                }
            }

            $totalCost = $alreadyBilled + $chargeAmount;

            if ($totalCost > 0) {
                $this->walletService->logDebitOnly($consumerId, $totalCost, 'call_deduction', 'App\Models\CallSession', $session->id);

                $provider = User::with('astrologer')->findOrFail($providerId);
                $pricing = $this->pricingCalculator->calculate($provider->astrologer, 'call');
                $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? 70.0);
                $totalCreditAmount = round(($totalCost * $astrologerSharePct) / 100, 2);

                $this->walletService->logCreditOnly($providerId, $totalCreditAmount, 'call_credit', 'App\Models\CallSession', $session->id);
            }

            $this->callRepo->update($sessionId, [
                'status' => 'completed',
                'ended_at' => $endTime,
                'duration_seconds' => $durationSeconds,
                'total_cost' => $totalCost,
            ]);

            $this->presenceService->setFree($consumerId);
            $this->presenceService->setFree($providerId);

            $session->refresh();
            return $session;
        }, 3);
    }

    /**
     * Reject an incoming call request.
     */
    public function rejectCall(int $sessionId, int $providerId): CallSession
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            $session = CallSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->provider_id !== (int) $providerId) {
                throw new Exception("Call session not found or unauthorized.", 403);
            }

            if (!in_array($session->status, ['initiated', 'ringing', 'waiting'])) {
                throw new Exception("This call cannot be rejected.");
            }

            $this->callRepo->update($sessionId, [
                'status' => 'rejected',
                'ended_at' => now(),
            ]);

            $this->presenceService->setFree($session->consumer_id);
            $this->presenceService->setFree($session->provider_id);

            $session->refresh();
            return $session;
        }, 3);
    }

    /**
     * Cancel an initiated call request by the caller.
     */
    public function cancelCall(int $sessionId, int $consumerId): CallSession
    {
        return DB::transaction(function () use ($sessionId, $consumerId) {
            $session = CallSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->consumer_id !== (int) $consumerId) {
                throw new Exception("You are not authorized to cancel this call.", 403);
            }

            if (!in_array($session->status, ['initiated', 'ringing', 'waiting'])) {
                throw new Exception("Only initiated or ringing calls can be cancelled.");
            }

            $this->callRepo->update($sessionId, [
                'status' => 'cancelled',
                'ended_at' => now(),
            ]);

            $this->presenceService->setFree($session->consumer_id);
            $this->presenceService->setFree($session->provider_id);

            $session->refresh();
            return $session;
        }, 3);
    }
}
