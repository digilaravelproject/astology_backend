<?php

namespace App\Jobs;

use App\Services\ExotelSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [2, 5, 10];
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $otp
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $smsService = new ExotelSmsService();
            $response = $smsService->sendOtp($this->phone, $this->otp);
            Log::info("SMS OTP dispatched successfully via queue to {$this->phone}");
        } catch (Throwable $e) {
            Log::error("SendSmsOtpJob failed for {$this->phone}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("SendSmsOtpJob permanently failed for {$this->phone}: " . ($exception ? $exception->getMessage() : 'Unknown error'));
    }
}
