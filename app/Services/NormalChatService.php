<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Kundli;
use App\Models\User;
use App\Models\Message;
use App\Models\AstrologerDefaultMessage;
use App\Models\Wallet;
use App\Repositories\ChatSessionRepository;
use App\Jobs\ChatBillingTickJob;
use App\Services\PricingCalculatorService;
use App\Services\PresenceService;
use App\Services\WalletService;
use App\Services\BlockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NormalChatService
{
    protected $chatRepo;
    protected $walletService;
    protected $presenceService;
    protected $pricingCalculator;
    protected $blockService;

    public function __construct(
        ChatSessionRepository $chatRepo,
        WalletService $walletService,
        PresenceService $presenceService,
        PricingCalculatorService $pricingCalculator,
        BlockService $blockService
    ) {
        $this->chatRepo = $chatRepo;
        $this->walletService = $walletService;
        $this->presenceService = $presenceService;
        $this->pricingCalculator = $pricingCalculator;
        $this->blockService = $blockService;
    }

    /**
     * Initiate a normal 1-on-1 chat session with atomic concurrency checks.
     */
    public function initiateChat(int $consumerId, int $providerId, ?string $question = null): ChatSession
    {
        return DB::transaction(function () use ($consumerId, $providerId, $question) {
            // 1. Bidirectional block check
            if ($this->blockService->isBlockedBidirectional($consumerId, $providerId)) {
                throw new Exception("You cannot initiate a chat with this user because of block status.");
            }

            // 2. Lock provider and validate astrologer eligibility
            $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
            $astrologer = $provider->astrologer;
            if (!$astrologer || !$astrologer->is_chat_enabled) {
                throw new Exception("Astrologer is not available for chat.");
            }

            // 3. Calculate customer rate
            $pricing = $this->pricingCalculator->calculate($astrologer, 'chat');
            $rate = (float) $pricing['customer_rate'];

            // 4. Check dynamic busy state of provider
            $isProviderChatBusy = ChatSession::where('provider_id', $providerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isProviderCallBusy = \App\Models\CallSession::where('provider_id', $providerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            $hasWaitingQueue = ChatSession::where('provider_id', $providerId)->where('status', 'waiting')->exists()
                || \App\Models\CallSession::where('provider_id', $providerId)->where('status', 'waiting')->exists();

            $isBusy = $isProviderChatBusy || $isProviderCallBusy || $hasWaitingQueue;

            // 5. Check dynamic busy state of consumer
            $isConsumerChatBusy = ChatSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();
            $isConsumerCallBusy = \App\Models\CallSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            if ($isConsumerChatBusy || $isConsumerCallBusy) {
                throw new Exception("You are already in an active session.");
            }

            // 6. Check existing pending or waiting requests for consumer
            $existingChatPending = ChatSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['initiated', 'waiting'])
                ->exists();
            $existingCallPending = \App\Models\CallSession::where('consumer_id', $consumerId)
                ->whereIn('status', ['initiated', 'ringing', 'waiting'])
                ->exists();
            if ($existingChatPending || $existingCallPending) {
                throw new Exception("You already have a pending or waiting request.");
            }

            // 7. Check minimum balance (5 minutes minimum)
            $balance = $this->walletService->getBalance($consumerId);
            if ($balance < $rate * 5) {
                throw new Exception("Insufficient balance. You need minimum " . ($rate * 5) . " in your wallet to start this chat.");
            }

            $status = $isBusy ? 'waiting' : 'initiated';

            /** @var ChatSession $session */
            $session = $this->chatRepo->create([
                'consumer_id' => $consumerId,
                'provider_id' => $providerId,
                'status' => $status,
                'rate_per_minute' => $rate,
                'question' => $question,
            ]);

            return $session;
        }, 3);
    }

    /**
     * Accept an initiated chat session and initialize conversation state.
     */
    public function acceptChat(int $sessionId, int $providerId): array
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->provider_id !== (int) $providerId || !in_array($session->status, ['initiated', 'waiting'])) {
                throw new Exception("The chat session is no longer valid or has been cancelled.");
            }

            $provider = User::where('id', $providerId)->lockForUpdate()->first();

            // Queue check
            if ($session->status === 'waiting') {
                $oldestWaitingSessionId = ChatSession::where('provider_id', $providerId)
                    ->where('status', 'waiting')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->value('id');

                if ((int) $oldestWaitingSessionId !== (int) $session->id) {
                    throw new Exception("Please accept the oldest waiting chat request first.");
                }
            }

            // Concurrency busy check under lock
            $isChatBusy = ChatSession::where('provider_id', $providerId)
                ->whereIn('status', ['accepted', 'ongoing'])
                ->where('id', '!=', $sessionId)
                ->exists();
            $isCallBusy = \App\Models\CallSession::where('provider_id', $providerId)
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();
            if ($isChatBusy || $isCallBusy) {
                throw new Exception("You are already in an active session.");
            }

            $this->chatRepo->update($sessionId, [
                'status' => 'ongoing',
                'started_at' => now(),
                'accepted_at' => now(),
                'last_billed_at' => now(),
            ]);

            $this->presenceService->setBusy($session->consumer_id, $sessionId);
            $this->presenceService->setBusy($providerId, $sessionId);

            $consumer = User::findOrFail($session->consumer_id);

            $systemMessage = null;
            // Format & create consumer details system message ONLY if not sent in last 24h
            if ($this->shouldSendBirthDetails((int) $session->consumer_id, (int) $session->provider_id)) {
                $detailsMsg = $this->formatUserDetailsMessage($consumer, $session);
                $systemMessage = Message::create([
                    'chat_session_id' => $session->id,
                    'sender_id' => $session->consumer_id,
                    'receiver_id' => $session->provider_id,
                    'message' => $detailsMsg,
                    'type' => 'system',
                ]);
            }

            // Check & send astrologer default welcome message
            $defaultMessage = AstrologerDefaultMessage::where('astrologer_id', $providerId)
                ->where('is_default', true)
                ->first();

            $textMsg = null;
            if ($defaultMessage) {
                $personalizedMsg = $this->personalizeDefaultMessage($defaultMessage->content, $consumer, $provider, $session);
                $textMsg = Message::create([
                    'chat_session_id' => $session->id,
                    'sender_id' => $session->provider_id,
                    'receiver_id' => $session->consumer_id,
                    'message' => $personalizedMsg,
                    'type' => 'text',
                ]);
            }

            // Dispatch per-minute billing tick job
            ChatBillingTickJob::dispatch($sessionId)->delay(now()->addMinute());

            $session->refresh();
            $session->setRelation('consumer', $consumer);
            $session->setRelation('provider', $provider->loadMissing('astrologer'));

            return [
                'session' => $session,
                'system_message' => $systemMessage,
                'default_message' => $textMsg,
            ];
        }, 3);
    }

    /**
     * End a normal chat session and process final balance settlement.
     */
    public function endChat(int $sessionId, ?int $userId = null): ChatSession
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();
            if (!$session || !in_array($session->status, ['initiated', 'accepted', 'ongoing'])) {
                return $session;
            }

            // Authorization check
            if ($userId && (int) $session->consumer_id !== (int) $userId && (int) $session->provider_id !== (int) $userId) {
                throw new Exception("You are not authorized to end this chat.");
            }

            $consumerId = (int) $session->consumer_id;
            $providerId = (int) $session->provider_id;

            // Lock wallets in deterministic order to prevent deadlock
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

                    // Calculate astrologer revenue share
                    $provider = User::with('astrologer')->findOrFail($providerId);
                    $pricing = $this->pricingCalculator->calculate($provider->astrologer, 'chat');
                    $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? 70.0);
                    $creditAmount = round(($chargeAmount * $astrologerSharePct) / 100, 2);

                    $this->walletService->creditBalanceOnly($providerId, $creditAmount);
                }
            }

            $totalCost = $alreadyBilled + $chargeAmount;

            if ($totalCost > 0) {
                $this->walletService->logDebitOnly($consumerId, $totalCost, 'chat_deduction', 'App\Models\ChatSession', $session->id);

                $provider = User::with('astrologer')->findOrFail($providerId);
                $pricing = $this->pricingCalculator->calculate($provider->astrologer, 'chat');
                $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? 70.0);
                $totalCreditAmount = round(($totalCost * $astrologerSharePct) / 100, 2);

                $this->walletService->logCreditOnly($providerId, $totalCreditAmount, 'chat_credit', 'App\Models\ChatSession', $session->id);
            }

            $this->chatRepo->update($sessionId, [
                'status' => 'completed',
                'ended_at' => $endTime,
                'duration_seconds' => $durationSeconds,
                'total_cost' => $totalCost,
            ]);

            // Clean up any stale or hanging sessions between these two users so they never auto-reopen
            ChatSession::where('consumer_id', $consumerId)
                ->where('provider_id', $providerId)
                ->whereIn('status', ['initiated', 'waiting', 'accepted', 'ongoing', 'active'])
                ->where('id', '!=', $sessionId)
                ->update([
                    'status'   => 'cancelled',
                    'ended_at' => $endTime,
                ]);

            $this->presenceService->setFree($consumerId);
            $this->presenceService->setFree($providerId);

            User::whereIn('id', [$consumerId, $providerId])
                ->update(['is_busy' => false, 'busy_session_id' => null]);

            $session->refresh();
            return $session;
        }, 3);
    }

    /**
     * Reject an initiated or waiting chat request.
     */
    public function rejectChat(int $sessionId, int $providerId): ChatSession
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->provider_id !== (int) $providerId) {
                throw new Exception("Chat session not found or unauthorized.", 403);
            }

            if (!in_array($session->status, ['initiated', 'waiting'])) {
                throw new Exception("Only pending or waiting chats can be rejected.");
            }

            $this->chatRepo->update($sessionId, [
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
     * Cancel an initiated or waiting chat by the consumer.
     */
    public function cancelChat(int $sessionId, int $consumerId): ChatSession
    {
        return DB::transaction(function () use ($sessionId, $consumerId) {
            $session = ChatSession::where('id', $sessionId)->lockForUpdate()->first();

            if (!$session || (int) $session->consumer_id !== (int) $consumerId) {
                throw new Exception("You are not authorized to cancel this chat.", 403);
            }

            if (!in_array($session->status, ['initiated', 'waiting'])) {
                throw new Exception("Only initiated or waiting chats can be cancelled.");
            }

            $this->chatRepo->update($sessionId, [
                'status' => 'cancelled',
                'ended_at' => now(),
            ]);

            $this->presenceService->setFree($session->consumer_id);
            $this->presenceService->setFree($session->provider_id);

            $session->refresh();
            return $session;
        }, 3);
    }

    /**
     * Check if user birth details have already been sent to this astrologer within the last 24 hours.
     */
    protected function shouldSendBirthDetails(int $consumerId, int $providerId): bool
    {
        $recentSessionIds = ChatSession::where(function ($q) use ($consumerId, $providerId) {
                $q->where('consumer_id', $consumerId)->where('provider_id', $providerId);
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->pluck('id');

        if ($recentSessionIds->isNotEmpty()) {
            $alreadySent = Message::whereIn('chat_session_id', $recentSessionIds)
                ->where('sender_id', $consumerId)
                ->where('receiver_id', $providerId)
                ->where('type', 'system')
                ->where(function ($q) {
                    $q->where('message', 'like', '%Birth Details%')
                      ->orWhere('message', 'like', '%Kundli & Consultation Details%');
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($alreadySent) {
                return false;
            }
        }

        return true;
    }

    protected function formatUserDetailsMessage(User $consumer, ChatSession $session): string
    {
        // Fallback to user's saved Kundli if user profile birth details are incomplete
        $savedKundli = null;
        if (empty($consumer->date_of_birth) && empty($consumer->time_of_birth)) {
            $savedKundli = Kundli::where('user_id', $consumer->id)->latest()->first();
        }

        $name = $consumer->name ?? $savedKundli?->name ?? 'User';
        $gender = $consumer->gender ? ucfirst($consumer->gender) : ($savedKundli?->gender ? ucfirst($savedKundli->gender) : 'N/A');

        $dob = 'N/A';
        if ($consumer->date_of_birth) {
            $dob = $consumer->date_of_birth->timezone('Asia/Kolkata')->format('d M Y');
        } elseif ($savedKundli?->birth_date) {
            $dob = \Carbon\Carbon::parse($savedKundli->birth_date, 'Asia/Kolkata')->format('d M Y');
        }

        $tob = 'N/A';
        $rawTob = $consumer->time_of_birth ?? $savedKundli?->birth_time;
        if ($rawTob) {
            try {
                $tob = \Carbon\Carbon::parse($rawTob, 'Asia/Kolkata')->format('h:i A');
            } catch (\Throwable $e) {
                $tob = (string) $rawTob;
            }
        }

        $pob = $consumer->place_of_birth ?? $savedKundli?->birth_place ?? 'N/A';
        $occupation = $consumer->occupation ?? 'N/A';
        $status = $consumer->relationship_status ?? 'N/A';
        $question = $session->question ? trim($session->question) : 'General Consultation';

        return "--- 👤 Kundli & Consultation Details ---\n" .
               "• Name: {$name}\n" .
               "• Gender: {$gender}\n" .
               "• DOB: {$dob}\n" .
               "• TOB: {$tob}\n" .
               "• POB: {$pob}\n" .
               "• Marital Status: {$status}\n" .
               "• Occupation: {$occupation}\n" .
               "• Prashna/Topic: {$question}\n" .
               "---------------------------------------";
    }

    protected function personalizeDefaultMessage(string $content, User $consumer, User $provider, ChatSession $session): string
    {
        $replacements = [
            '{name}' => $consumer->name ?? 'User',
            '{user_name}' => $consumer->name ?? 'User',
            '{astrologer_name}' => $provider->name ?? 'Astrologer',
            '{date}' => now()->format('d M Y'),
            '{time}' => now()->format('h:i A'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
