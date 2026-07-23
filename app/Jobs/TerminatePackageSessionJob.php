<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PackageSubSession;
use App\Services\SessionTimerService;
use Illuminate\Support\Facades\Log;

class TerminatePackageSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    protected $subSessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $subSessionId)
    {
        $this->subSessionId = $subSessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(SessionTimerService $timerService)
    {
        try {
            $subSession = PackageSubSession::find($this->subSessionId);
            if ($subSession && is_null($subSession->ended_at)) {
                // The session is still active, terminate it forcefully.
                $timerService->endSubSession($this->subSessionId, null, true);
            }
        } catch (\Exception $e) {
            Log::error("Failed to terminate package session: " . $e->getMessage());
        }
    }
}
