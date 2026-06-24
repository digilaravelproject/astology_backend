<?php

namespace App\Services;

use App\Repositories\CallSessionRepository;
use App\Models\User;
use App\Jobs\CallBillingTickJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CallService
{
    protected $callRepo;
    protected $walletService;
    protected $presenceService;

    public function __construct(
        CallSessionRepository $callRepo,
        WalletService $walletService,
        PresenceService $presenceService
    ) {
        $this->callRepo = $callRepo;
        $this->walletService = $walletService;
        $this->presenceService = $presenceService;
    }

    public function getSession($sessionId)
    {
        return $this->callRepo->findById($sessionId);
    }

    /**
     * Initiate a call session with rate validation and balance check.
     */
    public function initiateCall($consumerId, $providerId)
    {
        return DB::transaction(function () use ($consumerId, $providerId) {
            try {
                $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
                $astrologer = $provider->astrologer;
                if (!$astrologer || !$astrologer->is_call_enabled) {
                    throw new Exception("Astrologer is not available for calls.");
                }

                $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                $pricing = $pricingCalculator->calculate($astrologer, 'call');
                $rate = $pricing['customer_rate'];

                // Dynamic busy status check
                $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
                    ->whereIn('status', ['accepted', 'ongoing'], 'and', false)
                    ->exists();
                $isCallBusy = \App\Models\CallSession::where('provider_id', $providerId)
                    ->whereIn('status', ['ringing', 'accepted', 'ongoing'], 'and', false)
                    ->exists();
                $hasWaitingQueue = \App\Models\CallSession::where('provider_id', $providerId)
                    ->where('status', 'waiting')
                    ->exists();
                $isBusy = $isChatBusy || $isCallBusy || $hasWaitingQueue;

                // Dynamic check for consumer
                $isConsumerChatBusy = \App\Models\ChatSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['accepted', 'ongoing'], 'and', false)
                    ->exists();
                $isConsumerCallBusy = \App\Models\CallSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['ringing', 'accepted', 'ongoing'], 'and', false)
                    ->exists();
                if ($isConsumerChatBusy || $isConsumerCallBusy) {
                    throw new Exception("You are already in an active session.");
                }

                // Prevent duplicate pending or waiting requests
                $existingChatPending = \App\Models\ChatSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['initiated', 'waiting'], 'and', false)
                    ->exists();
                $existingCallPending = \App\Models\CallSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['initiated', 'ringing', 'waiting'], 'and', false)
                    ->exists();
                if ($existingChatPending || $existingCallPending) {
                    throw new Exception("You already have a pending or waiting request.");
                }

                // Check minimum balance (5 minutes minimum to start)
                $balance = $this->walletService->getBalance($consumerId);
                if ($balance < $rate * 5) {
                    throw new Exception("Insufficient balance. You need minimum " . ($rate * 5) . " in your wallet to start this call.");
                }

                $status = $isBusy ? 'waiting' : 'initiated';

                $session = $this->callRepo->create([
                    'consumer_id' => $consumerId,
                    'provider_id' => $providerId,
                    'call_type'   => 'audio',
                    'status' => $status,
                    'rate_per_minute' => $rate,
                ]);

                if ($status === 'initiated') {
                    // Dispatch timeout cleanup (60 seconds ringing timeout)
                    \App\Jobs\CleanupMissedSessionJob::dispatch($session->id, 'call')->delay(now()->addSeconds(60));
                }

                return $session;

            } catch (Exception $e) {
                Log::error("Call Initiation Failed: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }
    
    /**
     * Accept an initiated call and mark participants as busy.
     */
    public function acceptCall($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                // Lock the session row FIRST (prevents deadlock with missedCall/endCall/rejectCall)
                $session = \App\Models\CallSession::where('id', $sessionId)->lockForUpdate()->first();

                if (!$session || $session->provider_id != $providerId || !in_array($session->status, ['initiated', 'ringing', 'waiting'])) {
                    throw new Exception("The call session is no longer valid or has been cancelled.");
                }

                // Now lock provider row to prevent concurrent accepts
                $provider = User::where('id', $providerId)->lockForUpdate()->first();

                // Check dynamic busy check under lock to prevent double booking
                $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
                    ->whereIn('status', ['accepted', 'ongoing'], 'and', false)
                    ->exists();
                $isCallBusy = \App\Models\CallSession::where('provider_id', $providerId)
                    ->whereIn('status', ['ringing', 'accepted', 'ongoing'], 'and', false)
                    ->where('id', '!=', $sessionId)
                    ->exists();
                if ($isChatBusy || $isCallBusy) {
                    throw new Exception("You are already in an active session.");
                }
                
                // Atomically update participants
                $this->callRepo->update($sessionId, [
                    'status' => 'ongoing',
                    'started_at' => now(),
                    'last_billed_at' => now()
                ]);
                
                $this->presenceService->setBusy($session->consumer_id, $sessionId);
                $this->presenceService->setBusy($providerId, $sessionId);
                
                // Start billing ticker (delayed by 1 minute)
                CallBillingTickJob::dispatch($sessionId)->delay(now()->addMinute());
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Call Acceptance Failed: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * Reject an initiated or waiting call request.
     */
    public function rejectCall($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                $session = \App\Models\CallSession::where('id', $sessionId)->lockForUpdate()->first();
                if (!$session) {
                    throw new Exception("Call session not found.");
                }

                if ($session->provider_id != $providerId) {
                    throw new Exception("You are not authorized to reject this call.");
                }

                if (!in_array($session->status, ['initiated', 'ringing', 'waiting'])) {
                    throw new Exception("Only initiated, ringing, or waiting calls can be rejected.");
                }

                $this->callRepo->update($sessionId, [
                    'status' => 'rejected',
                    'ended_at' => now(),
                ]);

                // Reset presence status for both users
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Rejecting Call Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * End a call session and calculate final costs.
     */
    public function endCall($sessionId, $userId = null)
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            try {
                $session = \App\Models\CallSession::where('id', $sessionId)->lockForUpdate()->first();
                
                if (!$session || !in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                    return $session;
                }

                // Security check: Only participants can end the call (unless userId is null, e.g., from a Job)
                if ($userId && $session->consumer_id != $userId && $session->provider_id != $userId) {
                    throw new Exception("You are not authorized to end this call.");
                }

                // Lock wallets in consistent order (MIN user_id first) to prevent AB-BA deadlock
                $consumerId = $session->consumer_id;
                $providerId = $session->provider_id;
                if ($consumerId < $providerId) {
                    $consumerWallet = \App\Models\Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
                    $providerWallet = \App\Models\Wallet::where('user_id', $providerId)->lockForUpdate()->first();
                } else {
                    $providerWallet = \App\Models\Wallet::where('user_id', $providerId)->lockForUpdate()->first();
                    $consumerWallet = \App\Models\Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
                }

                $endTime = now();
                $durationSeconds = $session->started_at ? $session->started_at->diffInSeconds($endTime) : 0;
                $finalCost = $this->calculateCost($durationSeconds, $session->rate_per_minute);
                
                // Calculate unbilled amount
                $alreadyBilled = $session->total_cost ?? 0;
                $unbilledBalance = $finalCost - $alreadyBilled;

                $chargeAmount = 0.00;
                if ($unbilledBalance > 0 && $consumerWallet) {
                    $chargeAmount = min($unbilledBalance, $consumerWallet->balance);
                    if ($chargeAmount > 0) {
                        $this->walletService->debitBalanceOnly($session->consumer_id, $chargeAmount);
                        
                        // Calculate astrologer share based on active offer or global fallback
                        $provider = \App\Models\User::with('astrologer')->findOrFail($providerId);
                        $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                        $pricing = $pricingCalculator->calculate($provider->astrologer, 'call');
                        $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
                        $creditAmount = round(($chargeAmount * $astrologerSharePct) / 100, 2);

                        $this->walletService->creditBalanceOnly($session->provider_id, $creditAmount);
                    }
                }

                $totalCost = $alreadyBilled + $chargeAmount;

                // Create consolidated transaction records for the entire session at once!
                if ($totalCost > 0) {
                    $this->walletService->logDebitOnly($session->consumer_id, $totalCost, 'call_deduction', 'App\Models\CallSession', $session->id);
                    
                    $provider = \App\Models\User::with('astrologer')->findOrFail($providerId);
                    $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                    $pricing = $pricingCalculator->calculate($provider->astrologer, 'call');
                    $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
                    $totalCreditAmount = round(($totalCost * $astrologerSharePct) / 100, 2);

                    $this->walletService->logCreditOnly($session->provider_id, $totalCreditAmount, 'call_credit', 'App\Models\CallSession', $session->id);
                }

                $this->callRepo->update($sessionId, [
                    'status' => 'completed',
                    'ended_at' => $endTime,
                    'duration_seconds' => $durationSeconds,
                    'total_cost' => $totalCost,
                ]);

                // Reset presence status for both users
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Ending Call Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    public function calculateCost($durationSeconds, $rate)
    {
        $activeMinutes = ceil($durationSeconds / 60);
        return $activeMinutes * $rate;
    }

    /**
     * Mark a call as missed (timed-out without being answered).
     * Called by CleanupMissedSessionJob instead of endCall().
     * No billing occurs for missed calls.
     */
    public function missedCall($sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            try {
                $session = \App\Models\CallSession::where('id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                // Only act if still in the 'initiated' state (not yet answered or already cleaned up)
                if (!$session || $session->status !== 'initiated') {
                    return $session;
                }

                $this->callRepo->update($sessionId, [
                    'status'   => 'missed',
                    'ended_at' => now(),
                ]);

                // Free both parties' presence so they can start new sessions
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Marking call as missed failed: session {$sessionId} — " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * Cancel a pending/waiting call request initiated by the consumer.
     * Only the consumer who created the session may cancel it.
     */
    public function cancelCall($sessionId, $consumerId)
    {
        return DB::transaction(function () use ($sessionId, $consumerId) {
            try {
                $session = \App\Models\CallSession::where('id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                if (!$session) {
                    throw new Exception('Call session not found.');
                }

                if ($session->consumer_id != $consumerId) {
                    throw new Exception('You are not authorized to cancel this call.');
                }

                if (!in_array($session->status, ['initiated', 'ringing'])) {
                    throw new Exception('Only ringing calls can be cancelled.');
                }

                $this->callRepo->update($sessionId, [
                    'status'   => 'missed',
                    'ended_at' => now(),
                ]);

                // Reset presence for both parties
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Cancelling call failed: session {$sessionId} — " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }
}

