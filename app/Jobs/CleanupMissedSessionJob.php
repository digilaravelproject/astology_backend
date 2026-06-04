<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Services\CallService;
use App\Services\ChatService;
use App\Events\CallEnded;
use App\Events\ChatEnded;
use Illuminate\Support\Facades\Log;

class CleanupMissedSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;
    protected $type; // 'call' or 'chat'

    public function __construct($sessionId, $type = 'call')
    {
        $this->sessionId = $sessionId;
        $this->type = $type;
    }

    public function handle()
    {
        if ($this->type === 'call') {
            $session = CallSession::find($this->sessionId);
            // Only act if the call is still in 'initiated' state (unanswered)
            if ($session && $session->status === 'initiated') {
                $callService = app(CallService::class);
                $callService->missedCall($this->sessionId);
                // CallDismissed (not CallEnded) — this was a missed/timed-out ring
                broadcast(new \App\Events\CallDismissed($session->refresh(), null, 'timeout'));
                Log::info("Call session {$this->sessionId} timed out (missed).");
            }
        } else {
            $session = ChatSession::find($this->sessionId);
            if ($session && $session->status === 'initiated') {
                $chatService = app(ChatService::class);
                $timedOutSession = $chatService->systemTimeoutChat($this->sessionId);
                broadcast(new \App\Events\ChatDismissed($timedOutSession, null, 'timeout'));
                broadcast(new \App\Events\ChatQueueUpdated($timedOutSession->provider_id, $timedOutSession, 'timeout'));
                Log::info("Chat session {$this->sessionId} timed out (missed).");
            }
        }
    }
}
