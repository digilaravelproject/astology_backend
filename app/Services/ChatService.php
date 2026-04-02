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

    /**
     * Initiate a chat session with rate validation and balance check.
     */
    public function initiateChat($consumerId, $providerId)
    {
        return DB::transaction(function () use ($consumerId, $providerId) {
            try {
                $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
                
                if (!$provider->is_online) {
                    throw new Exception("Astrologer is currently offline.");
                }

                if ($provider->is_busy) {
                    throw new Exception("Astrologer is currently busy or offline.");
                }

                $consumer = User::findOrFail($consumerId);
                if ($consumer->is_busy) {
                    throw new Exception("You are already in an active session.");
                }
                
                $rate = $provider->astrologer->chat_rate_per_minute ?? 15.00;
                
                $balance = $this->walletService->getBalance($consumerId);
                if ($balance < $rate * 5) {
                    throw new Exception("Insufficient balance. Minimum 5 minutes required (" . ($rate * 5) . ").");
                }

                return $this->chatRepo->create([
                    'consumer_id' => $consumerId,
                    'provider_id' => $providerId,
                    'status' => 'initiated',
                    'rate_per_minute' => $rate,
                ]);

            } catch (Exception $e) {
                Log::error("Chat Initiation Failed: " . $e->getMessage());
                throw $e;
            }
        });
    }
    
    /**
     * Accept an initiated chat and mark participants as busy.
     */
    public function acceptChat($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                $session = $this->chatRepo->findById($sessionId);
                if (!$session || $session->provider_id != $providerId || $session->status !== 'initiated') {
                    throw new Exception("Chat cannot be accepted. Session might have expired.");
                }
                
                $this->chatRepo->update($sessionId, [
                    'status' => 'ongoing',
                    'started_at' => now(),
                    'last_billed_at' => now()
                ]);
                
                $this->presenceService->setBusy($session->consumer_id, $sessionId);
                $this->presenceService->setBusy($providerId, $sessionId);
                
                // Start billing ticker (delayed by 1 minute)
                ChatBillingTickJob::dispatch($sessionId)->delay(now()->addMinute());
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Chat Acceptance Failed: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * End a chat session and calculate final costs.
     */
    public function endChat($sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            try {
                $session = $this->chatRepo->findById($sessionId);
                if (!$session || !in_array($session->status, ['initiated', 'accepted', 'ongoing'])) {
                    return $session;
                }

                $endTime = now();
                $durationSeconds = $session->started_at ? $session->started_at->diffInSeconds($endTime) : 0;
                $finalCost = ceil($durationSeconds / 60) * $session->rate_per_minute;
                
                // Calculate unbilled amount
                $alreadyBilled = $session->total_cost ?? 0;
                $unbilledBalance = $finalCost - $alreadyBilled;

                if ($unbilledBalance > 0) {
                    $this->walletService->deductForChat($session->consumer_id, $unbilledBalance, $session->id);
                    $this->walletService->creditProviderForChat($session->provider_id, $unbilledBalance, $session->id);
                }

                $this->chatRepo->update($sessionId, [
                    'status' => 'completed',
                    'ended_at' => $endTime,
                    'duration_seconds' => $durationSeconds,
                    'total_cost' => $finalCost,
                ]);

                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Ending Chat Failed: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
