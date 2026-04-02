<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChatSession;
use App\Services\WalletService;
use App\Services\ChatService;
use App\Events\ChatEnded;
use Exception;

class ChatBillingTickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function handle(WalletService $walletService, ChatService $chatService)
    {
        $session = ChatSession::find($this->sessionId);
        
        if (!$session || $session->status !== 'ongoing') {
            return;
        }

        try {
            $deducted = $walletService->deductForChat($session->consumer_id, $session->rate_per_minute, $session->id);
            
            if (!$deducted) {
                // Insufficient funds, end the chat
                $chatService->endChat($session->id);
                event(new ChatEnded($session, $session->consumer_id));
                return;
            }

            // Credit provider
            $walletService->creditProviderForChat($session->provider_id, $session->rate_per_minute, $session->id);

            // Update session
            $session->last_billed_at = now();
            $session->total_cost += $session->rate_per_minute;
            $session->save();

            // Re-dispatch for next minute
            ChatBillingTickJob::dispatch($this->sessionId)->delay(now()->addMinute());

        } catch (Exception $e) {
            $chatService->endChat($session->id);
            event(new ChatEnded($session, $session->consumer_id));
        }
    }
}
