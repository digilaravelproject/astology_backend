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
        $session = CallSession::find($this->sessionId);
        
        if (!$session || $session->status !== 'ongoing') {
            return;
        }

        try {
            $deducted = $walletService->deductForCall($session->consumer_id, $session->rate_per_minute, $session->id);
            
            if (!$deducted) {
                // Insufficient funds, end the call
                $callService->endCall($session->id);
                // Broadcast end event
                event(new CallEnded($session, $session->consumer_id)); // System ends it on behalf of consumer
                return;
            }

            // Credit provider
            $walletService->creditProviderForCall($session->provider_id, $session->rate_per_minute, $session->id);

            // Update session
            $session->last_billed_at = now();
            $session->total_cost += $session->rate_per_minute;
            $session->save();

            // Re-dispatch for next minute
            CallBillingTickJob::dispatch($this->sessionId)->delay(now()->addMinute());

        } catch (Exception $e) {
            // End the call on error
            $callService->endCall($session->id);
            event(new CallEnded($session, $session->consumer_id));
        }
    }
}
