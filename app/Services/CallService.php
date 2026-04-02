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

    /**
     * Initiate a call session with rate validation and balance check.
     */
    public function initiateCall($consumerId, $providerId)
    {
        return DB::transaction(function () use ($consumerId, $providerId) {
            try {
                // Eager load provider and astrologer details to prevent N+1
                $provider = User::with('astrologer')->lockForUpdate()->findOrFail($providerId);
                
                if (!$provider->is_online) {
                    throw new Exception("Astrologer is currently offline.");
                }

                if ($provider->is_busy) {
                    throw new Exception("Astrologer is currently busy with another session.");
                }

                $consumer = User::findOrFail($consumerId);
                if ($consumer->is_busy) {
                    throw new Exception("You are already in an active session.");
                }

                $rate = $provider->astrologer->call_rate_per_minute ?? 15.00;
                
                // Check minimum balance (5 minutes minimum to start)
                $balance = $this->walletService->getBalance($consumerId);
                if ($balance < $rate * 5) {
                    throw new Exception("Insufficient balance. You need minimum " . ($rate * 5) . " in your wallet to start this call.");
                }

                return $this->callRepo->create([
                    'consumer_id' => $consumerId,
                    'provider_id' => $providerId,
                    'status' => 'initiated',
                    'rate_per_minute' => $rate,
                ]);

            } catch (Exception $e) {
                Log::error("Call Initiation Failed: " . $e->getMessage());
                throw $e;
            }
        });
    }
    
    /**
     * Accept an initiated call and mark participants as busy.
     */
    public function acceptCall($sessionId, $providerId)
    {
        return DB::transaction(function () use ($sessionId, $providerId) {
            try {
                $session = $this->callRepo->findById($sessionId);
                
                if (!$session || $session->provider_id != $providerId || !in_array($session->status, ['initiated', 'ringing'])) {
                    throw new Exception("The call session is no longer valid or has been cancelled.");
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
        });
    }

    /**
     * End a call session and calculate final costs.
     */
    public function endCall($sessionId)
    {
        return DB::transaction(function () use ($sessionId) {
            try {
                $session = $this->callRepo->findById($sessionId);
                if (!$session || !in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                    return $session;
                }

                $endTime = now();
                $durationSeconds = $session->started_at ? $session->started_at->diffInSeconds($endTime) : 0;
                $finalCost = $this->calculateCost($durationSeconds, $session->rate_per_minute);
                
                // Calculate unbilled amount (final cost minus what's already been deducted by jobs)
                $alreadyBilled = $session->total_cost ?? 0;
                $unbilledBalance = $finalCost - $alreadyBilled;

                if ($unbilledBalance > 0) {
                    $this->walletService->deductForCall($session->consumer_id, $unbilledBalance, $session->id);
                    $this->walletService->creditProviderForCall($session->provider_id, $unbilledBalance, $session->id);
                }

                $this->callRepo->update($sessionId, [
                    'status' => 'completed',
                    'ended_at' => $endTime,
                    'duration_seconds' => $durationSeconds,
                    'total_cost' => $finalCost,
                ]);

                // Reset presence status for both users
                $this->presenceService->setFree($session->consumer_id);
                $this->presenceService->setFree($session->provider_id);
                
                $session->refresh();
                return $session;

            } catch (Exception $e) {
                Log::error("Ending Call Failed: " . $session->id . " error: " . $e->getMessage());
                throw $e;
            }
        });
    }

    public function calculateCost($durationSeconds, $rate)
    {
        $activeMinutes = ceil($durationSeconds / 60);
        return $activeMinutes * $rate;
    }
}
