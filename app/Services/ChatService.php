<?php

namespace App\Services;

use App\Repositories\ChatSessionRepository;
use App\Models\User;
use App\Jobs\ChatBillingTickJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatService
{
    protected $chatRepo;
    protected $walletService;
    protected $presenceService;

    public function __construct(
        ChatSessionRepository $chatRepo,
        WalletService $walletService,
        PresenceService $presenceService
    ) {
        $this->chatRepo = $chatRepo;
        $this->walletService = $walletService;
        $this->presenceService = $presenceService;
    }

    public function getSession($sessionId)
    {
        return $this->chatRepo->findById($sessionId);
    }

    /**
     * Initiate a chat session with rate validation and balance check.
     */
    public function initiateChat($consumerId, $providerId, $question = null)
    {
        return DB::transaction(function () use ($consumerId, $providerId, $question) {
            try {
                $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
                
                $astrologer = $provider->astrologer;
                if (!$astrologer || !$astrologer->is_chat_enabled) {
                    throw new Exception("Astrologer is not available for chat.");
                }

                // Dynamic busy status check
                $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
                    ->whereIn('status', ['accepted', 'ongoing'])
                    ->exists();
                $isCallBusy = \App\Models\CallSession::where('provider_id', $providerId)
                    ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                    ->exists();
                $hasWaitingQueue = \App\Models\ChatSession::where('provider_id', $providerId)
                    ->where('status', 'waiting')
                    ->exists();
                $isBusy = $isChatBusy || $isCallBusy || $hasWaitingQueue;

                // Dynamic check for consumer
                $isConsumerChatBusy = \App\Models\ChatSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['accepted', 'ongoing'])
                    ->exists();
                $isConsumerCallBusy = \App\Models\CallSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                    ->exists();
                if ($isConsumerChatBusy || $isConsumerCallBusy) {
                    throw new Exception("You are already in an active session.");
                }

                // Prevent duplicate pending or waiting requests
                $existingChatPending = \App\Models\ChatSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['initiated', 'waiting'])
                    ->exists();
                $existingCallPending = \App\Models\CallSession::where('consumer_id', $consumerId)
                    ->whereIn('status', ['initiated', 'ringing', 'waiting'])
                    ->exists();
                if ($existingChatPending || $existingCallPending) {
                    throw new Exception("You already have a pending or waiting request.");
                }
                
                $astrologer = $provider->astrologer;
                if (!$astrologer || !$astrologer->is_chat_enabled) {
                    throw new Exception("Astrologer is not available for chat.");
                }

                $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                $pricing = $pricingCalculator->calculate($astrologer, 'chat');
                $rate = $pricing['customer_rate'];
                
                $balance = $this->walletService->getBalance($consumerId);

                // Skip minimum balance check if user has an active prepaid package with this astrologer.
                // Package users have already pre-paid, so wallet check is not applicable.
                $hasActivePackage = \App\Models\PackagePurchase::where('user_id', $consumerId)
                    ->where('astrologer_id', $providerId)
                    ->where('status', 'active')
                    ->where('remaining_duration', '>', 0)
                    ->exists();

                if (!$hasActivePackage && $balance < $rate * 5) {
                    throw new Exception("Insufficient balance. Minimum 5 minutes required (" . ($rate * 5) . ").");
                }

                $status = $isBusy ? 'waiting' : 'initiated';

                $session = $this->chatRepo->create([
                    'consumer_id' => $consumerId,
                    'provider_id' => $providerId,
                    'status' => $status,
                    'rate_per_minute' => $rate,
                    'question' => $question,
                ]);
                
                if ($status === 'initiated') {
                    // Dispatch timeout cleanup (120 seconds ringing timeout)
                    \App\Jobs\CleanupMissedSessionJob::dispatch($session->id, 'chat')->delay(now()->addSeconds(120));
                }

                return $session;

            } catch (Exception $e) {
                Log::error("Chat Initiation Failed: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }
    
    /**
     * Accept an initiated chat and mark participants as busy.
     */
    public function acceptChat($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                // Lock provider row to prevent concurrent accepts
                $provider = User::where('id', $providerId)->lockForUpdate()->first();

                $session = \App\Models\ChatSession::where('id', $sessionId)->lockForUpdate()->first();
                if (!$session || $session->provider_id != $providerId || !in_array($session->status, ['initiated', 'waiting'])) {
                    throw new Exception("Chat cannot be accepted. Session might have expired.");
                }

                if ($session->status === 'waiting') {
                    $oldestWaitingSessionId = \App\Models\ChatSession::where('provider_id', $providerId)
                        ->where('status', 'waiting')
                        ->orderBy('created_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->value('id');

                    if ((int) $oldestWaitingSessionId !== (int) $session->id) {
                        throw new Exception("Please accept the oldest waiting chat request first.");
                    }
                }

                // Check dynamic busy check under lock to prevent double booking
                $isChatBusy = \App\Models\ChatSession::where('provider_id', $providerId)
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
                    'last_billed_at' => now()
                ]);
                
                $this->presenceService->setBusy($session->consumer_id, $sessionId);
                $this->presenceService->setBusy($providerId, $sessionId);

                // Fetch latest consumer profile details
                $consumer = User::findOrFail($session->consumer_id);

                // Format consumer details as system message
                $detailsMsg = $this->formatUserDetailsMessage($consumer, $session);

                // Store system message
                $systemMessage = \App\Models\Message::create([
                    'chat_session_id' => $session->id,
                    'sender_id' => $session->consumer_id,
                    'receiver_id' => $session->provider_id,
                    'message' => $detailsMsg,
                    'type' => 'system',
                ]);

                // Check for astrologer's active default message
                $defaultMessage = \App\Models\AstrologerDefaultMessage::where('astrologer_id', $providerId)
                    ->where('is_default', true)
                    ->first();

                $textMsg = null;
                if ($defaultMessage) {
                    $personalizedMsg = $this->personalizeDefaultMessage($defaultMessage->content, $consumer, $provider, $session);
                    
                    // Store personalized default message
                    $textMsg = \App\Models\Message::create([
                        'chat_session_id' => $session->id,
                        'sender_id' => $session->provider_id,
                        'receiver_id' => $session->consumer_id,
                        'message' => $personalizedMsg,
                        'type' => 'text',
                    ]);

                }
                
                // Start billing ticker ONLY if consumer does NOT have an active prepaid package for this astrologer.
                // If an active package purchase exists, billing is prepaid — no per-minute tick needed.
                $hasActivePackage = \App\Models\PackagePurchase::where('user_id', $session->consumer_id)
                    ->where('astrologer_id', $session->provider_id)
                    ->where('status', 'active')
                    ->where('remaining_duration', '>', 0)
                    ->exists();

                if (!$hasActivePackage) {
                    ChatBillingTickJob::dispatch($sessionId)->delay(now()->addMinute());
                }
                
                $session->refresh();
                $session->setRelation('consumer', $consumer);
                $session->setRelation('provider', $provider->loadMissing('astrologer'));

                return [
                    'session' => $session,
                    'system_message' => $systemMessage,
                    'default_message' => $textMsg,
                ];

            } catch (Exception $e) {
                Log::error("Chat Acceptance Failed: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    private function formatUserDetailsMessage($consumer, $session)
    {
        $lines = ["Birth Details:"];
        $lines[] = "- Name: " . ($consumer->name ?? 'N/A');
        
        $dob = $consumer->date_of_birth;
        $lines[] = "- Date of Birth: " . ($dob ? ($dob instanceof \Carbon\Carbon ? $dob->format('Y-m-d') : $dob) : 'N/A');
        
        $tob = $consumer->time_of_birth;
        $lines[] = "- Time of Birth: " . ($tob ? ($tob instanceof \Carbon\Carbon ? $tob->format('H:i') : $tob) : 'N/A');
        
        $lines[] = "- Place of Birth: " . ($consumer->place_of_birth ?? 'N/A');
        $lines[] = "- Gender: " . ($consumer->gender ?? 'N/A');
        
        if (isset($consumer->relationship_status)) {
            $lines[] = "- Relationship Status: " . ($consumer->relationship_status ?? 'N/A');
        }
        if (isset($consumer->occupation)) {
            $lines[] = "- Occupation: " . ($consumer->occupation ?? 'N/A');
        }
        if ($session->question) {
            $lines[] = "- Question: " . $session->question;
        }
        
        return implode("\n", $lines);
    }

    private function personalizeDefaultMessage($content, $consumer, $provider, $session)
    {
        $dob = $consumer->date_of_birth;
        $dobStr = $dob ? ($dob instanceof \Carbon\Carbon ? $dob->format('Y-m-d') : $dob) : 'N/A';

        $tob = $consumer->time_of_birth;
        $tobStr = $tob ? ($tob instanceof \Carbon\Carbon ? $tob->format('H:i') : $tob) : 'N/A';

        $replacements = [
            '{{user_name}}' => $consumer->name ?? 'User',
            '{{astrologer_name}}' => $provider->name ?? 'Astrologer',
            '{{date_of_birth}}' => $dobStr,
            '{{time_of_birth}}' => $tobStr,
            '{{place_of_birth}}' => $consumer->place_of_birth ?? 'N/A',
            '{{session_id}}' => $session->id,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Reject an initiated or waiting chat request.
     */
    public function rejectChat($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                $session = \App\Models\ChatSession::where('id', $sessionId)->lockForUpdate()->first();
                if (!$session) {
                    throw new Exception("Chat session not found.");
                }

                if ($session->provider_id != $providerId) {
                    throw new Exception("You are not authorized to reject this chat.");
                }

                if (!in_array($session->status, ['initiated', 'waiting'])) {
                    throw new Exception("Only initiated or waiting chats can be rejected.");
                }

                $this->chatRepo->update($sessionId, [
                    'status' => 'rejected',
                    'ended_at' => now(),
                ]);

                // Reset presence status for both users
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Rejecting Chat Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * End a chat session and calculate final costs.
     */
    public function endChat($sessionId, $userId = null)
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            try {
                $session = \App\Models\ChatSession::where('id', $sessionId)->lockForUpdate()->first();
                if (!$session || !in_array($session->status, ['initiated', 'accepted', 'ongoing'])) {
                    return $session;
                }

                // Security check: Only participants (or system) can end the chat
                if ($userId && $session->consumer_id != $userId && $session->provider_id != $userId) {
                    throw new Exception("You are not authorized to end this chat.");
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
                $durationSeconds = $session->started_at ? (int) $session->started_at->diffInSeconds($endTime) : 0;
                $finalCost = ceil($durationSeconds / 60) * $session->rate_per_minute;
                
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
                        $pricing = $pricingCalculator->calculate($provider->astrologer, 'chat');
                        $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
                        $creditAmount = round(($chargeAmount * $astrologerSharePct) / 100, 2);

                        $this->walletService->creditBalanceOnly($session->provider_id, $creditAmount);
                    }
                }

                $totalCost = $alreadyBilled + $chargeAmount;

                // Create consolidated transaction records for the entire session at once!
                if ($totalCost > 0) {
                    $this->walletService->logDebitOnly($session->consumer_id, $totalCost, 'chat_deduction', 'App\Models\ChatSession', $session->id);
                    
                    $provider = \App\Models\User::with('astrologer')->findOrFail($providerId);
                    $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                    $pricing = $pricingCalculator->calculate($provider->astrologer, 'chat');
                    $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
                    $totalCreditAmount = round(($totalCost * $astrologerSharePct) / 100, 2);

                    $this->walletService->logCreditOnly($session->provider_id, $totalCreditAmount, 'chat_credit', 'App\Models\ChatSession', $session->id);
                }

                $this->chatRepo->update($sessionId, [
                    'status' => 'completed',
                    'ended_at' => $endTime,
                    'duration_seconds' => $durationSeconds,
                    'total_cost' => $totalCost,
                ]);

                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Ending Chat Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * Retrieve chat history for a session with pagination and ownership check.
     */
    public function getMessagesForSession($sessionId, $userId)
    {
        $session = \App\Models\ChatSession::findOrFail($sessionId);

        // Security check: must be a participant of the session
        if ($session->consumer_id != $userId && $session->provider_id != $userId) {
            throw new Exception("You are not authorized to access this chat history.", 403);
        }

        return \App\Models\Message::where('chat_session_id', $sessionId)
            ->oldest()
            ->paginate(30);
    }

    public function getMessages($sessionId)
    {
        return \App\Models\Message::where('chat_session_id', $sessionId)
            ->oldest()
            ->paginate(30);
    }

    /**
     * Retrieve all chat sessions for a user with pagination.
     */
    public function getSessions($userId)
    {
        return $this->chatRepo->getSessionsByUserId($userId);
    }

    public function getUserSessions($userId)
    {
        return $this->chatRepo->getUserSessions($userId);
    }

    public function getAstrologerSessions($userId)
    {
        return $this->chatRepo->getAstrologerSessions($userId);
    }

    /**
     * Get the current active session (initiated or ongoing) for a user.
     */
    public function getActiveSession($userId)
    {
        return \App\Models\ChatSession::with(['consumer', 'provider.astrologer'])
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->whereIn('status', ['initiated', 'accepted', 'ongoing'])
            ->latest()
            ->first();
    }

    /**
     * Cancel/dismiss an initiated chat session by the consumer.
     */
    public function cancelChat($sessionId, $userId)
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            try {
                $session = \App\Models\ChatSession::where('id', $sessionId)->lockForUpdate()->first();
                if (!$session) {
                    throw new Exception("Chat session not found.");
                }

                // Security: Only the consumer can cancel their own initiated chat
                if ($session->consumer_id != $userId) {
                    throw new Exception("You are not authorized to cancel this chat.", 403);
                }

                if (in_array($session->status, ['cancelled', 'rejected', 'completed'])) {
                    throw new Exception("This chat is already {$session->status}.");
                }

                if (!in_array($session->status, ['initiated', 'waiting'])) {
                    throw new Exception("Only initiated or waiting chats can be cancelled.");
                }

                $this->chatRepo->update($sessionId, [
                    'status' => 'cancelled',
                    'ended_at' => now(),
                ]);

                // Reset busy status for both consumer and provider
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Cancelling Chat Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }

    /**
     * Automatically timeout/dismiss an initiated chat session by the system.
     */
    public function systemTimeoutChat($sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            try {
                $session = $this->chatRepo->findById($sessionId);
                if (!$session) {
                    throw new Exception("Chat session not found.");
                }

                if ($session->status !== 'initiated') {
                    return $session; // Already accepted or cancelled
                }

                $this->chatRepo->update($sessionId, [
                    'status' => 'cancelled',
                    'ended_at' => now(),
                ]);

                // Reset busy status for both consumer and provider
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);

                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("System Timing Out Chat Failed: session " . $sessionId . " error: " . $e->getMessage());
                throw $e;
            }
        }, 3);
    }
}
