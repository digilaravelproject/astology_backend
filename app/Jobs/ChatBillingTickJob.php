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
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($walletService) {
                // Lock the session
                $session = ChatSession::where('id', $this->sessionId)->lockForUpdate()->first();
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
                    throw new Exception("Insufficient balance for chat session tick.");
                }

                // Perform debit (throws exception on failure)
                $walletService->debitBalanceOnly($session->consumer_id, $session->rate_per_minute);

                // Calculate provider share based on active offer or global fallback
                $provider = \App\Models\User::with('astrologer')->findOrFail($providerId);
                $pricingCalculator = app(\App\Services\PricingCalculatorService::class);
                $pricing = $pricingCalculator->calculate($provider->astrologer, 'chat');
                $astrologerSharePct = (float) $pricing['astrologer_share_percentage'];
                $creditAmount = round(($session->rate_per_minute * $astrologerSharePct) / 100, 2);

                // Perform credit (throws exception on failure)
                $walletService->creditBalanceOnly($session->provider_id, $creditAmount);

                // Update session
                $session->last_billed_at = now();
                $session->total_cost += $session->rate_per_minute;
                $session->save();
            });

            // Re-dispatch for next minute
            ChatBillingTickJob::dispatch($this->sessionId)->delay(now()->addMinute());

        } catch (Exception $e) {
            $session = ChatSession::find($this->sessionId);
            if ($session && in_array($session->status, ['initiated', 'accepted', 'ongoing'])) {
                $chatService->endChat($session->id);
                event(new ChatEnded($session, $session->consumer_id));
            }
        }
    }
}
