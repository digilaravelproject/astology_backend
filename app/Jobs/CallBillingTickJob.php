<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CallSession;
use App\Services\WalletService;
use App\Services\CallService;
use App\Events\CallEnded;
use Exception;

class CallBillingTickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(WalletService $walletService, CallService $callService)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($walletService) {
                // Lock the session
                $session = CallSession::where('id', $this->sessionId)->lockForUpdate()->first();
                if (!$session || $session->status !== 'ongoing') {
                    throw new Exception("Session is not ongoing or not found.");
                }

                // Lock both wallets in consistent order (MIN user_id first) to prevent AB-BA deadlock
                $consumerId = $session->consumer_id;
                $providerId = $session->provider_id;
                if ($consumerId < $providerId) {
                    $consumerWallet = \App\Models\Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
                    $providerWallet = \App\Models\Wallet::where('user_id', $providerId)->lockForUpdate()->first();
                } else {
                    $providerWallet = \App\Models\Wallet::where('user_id', $providerId)->lockForUpdate()->first();
                    $consumerWallet = \App\Models\Wallet::where('user_id', $consumerId)->lockForUpdate()->first();
                }
                if (!$consumerWallet || $consumerWallet->balance < $session->rate_per_minute) {
                    throw new Exception("Insufficient balance for call session tick.");
                }

                // Perform debit (throws exception on failure)
                $walletService->deductForCall($session->consumer_id, $session->rate_per_minute, $session->id);

                // Perform credit (throws exception on failure)
                $walletService->creditProviderForCall($session->provider_id, $session->rate_per_minute, $session->id);

                // Update session
                $session->last_billed_at = now();
                $session->total_cost += $session->rate_per_minute;
                $session->save();
            });

            // Re-dispatch for next minute
            CallBillingTickJob::dispatch($this->sessionId)->delay(now()->addMinute());

        } catch (Exception $e) {
            $session = CallSession::find($this->sessionId);
            if ($session && in_array($session->status, ['initiated', 'ringing', 'accepted', 'ongoing'])) {
                $callService->endCall($session->id);
                event(new CallEnded($session, $session->consumer_id));
            }
        }
    }
}
