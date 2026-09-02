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
        $isInsufficientBalance = false;
        $sessionOngoing = false;

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($walletService, &$isInsufficientBalance, &$sessionOngoing) {
                // Lock the session
                $session = CallSession::where('id', $this->sessionId)->lockForUpdate()->first();
                if (!$session || $session->status !== 'ongoing') {
                    return; // Session is already finished or not active
                }

                $sessionOngoing = true;

                // 🛡️ PREPAID / PACKAGE SESSION FAIL-SAFE GUARD:
                // Only skip wallet debit if this specific session is tied to a PackageSubSession or rate_per_minute <= 0
                $isPrepaid = \App\Models\PackageSubSession::where('call_session_id', $this->sessionId)->exists()
                    || \App\Models\PackageSubSession::where('chat_session_id', $this->sessionId)->exists()
                    || (float) $session->rate_per_minute <= 0;

                if ($isPrepaid) {
                    \Illuminate\Support\Facades\Log::info("CallBillingTickJob: Session #{$this->sessionId} is prepaid package. Skipping wallet debit.");
                    return;
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
                    $isInsufficientBalance = true;
                    throw new Exception("Insufficient balance for call session tick.");
                }

                // Perform debit (throws exception on failure)
                $walletService->debitBalanceOnly($session->consumer_id, $session->rate_per_minute);

                // Calculate provider share dynamically based on active offer or global admin setting
                $provider = \App\Models\User::with('astrologer')->find($providerId);
                $adminCommission = (float) \App\Models\Setting::get('global_commission_percentage', \App\Models\Setting::get('global_admin_commission_rate', 20.00));
                $astrologerSharePct = 100 - $adminCommission;

                if ($provider && $provider->astrologer) {
                    $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                    $pricing = $pricingCalculator->calculate($provider->astrologer, 'call');
                    $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? $astrologerSharePct);
                }

                $creditAmount = round(($session->rate_per_minute * $astrologerSharePct) / 100, 2);

                // Perform credit (throws exception on failure)
                $walletService->creditBalanceOnly($session->provider_id, $creditAmount);

                // Update session
                $session->last_billed_at = now();
                $session->total_cost += $session->rate_per_minute;
                $session->save();
            });

            // Re-dispatch for next minute if session is still ongoing
            $currentSession = CallSession::find($this->sessionId);
            if ($currentSession && $currentSession->status === 'ongoing') {
                CallBillingTickJob::dispatch($this->sessionId)->delay(now()->addMinute());
            }

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::warning("CallBillingTickJob warning for session #{$this->sessionId}: " . $e->getMessage());

            $session = CallSession::find($this->sessionId);
            if ($session && $session->status === 'ongoing') {
                if ($isInsufficientBalance) {
                    // Genuine insufficient balance: gracefully end call
                    \Illuminate\Support\Facades\Log::info("Ending call #{$session->id} due to exhausted wallet balance.");
                    $callService->endCall($session->id);
                    event(new CallEnded($session, $session->consumer_id));
                } else {
                    // Transient error (e.g. temporary lock contention): retry tick without dropping the call
                    \Illuminate\Support\Facades\Log::info("Re-dispatching CallBillingTickJob for call #{$session->id} after transient error.");
                    CallBillingTickJob::dispatch($this->sessionId)->delay(now()->addSeconds(10));
                }
            }
        }
    }
}
