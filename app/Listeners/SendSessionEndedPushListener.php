<?php

namespace App\Listeners;

use App\Events\CallEnded;
use App\Events\ChatEnded;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use App\Services\PricingCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSessionEndedPushListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle session termination events for Chat or Call.
     *
     * @param ChatEnded|CallEnded $event
     */
    public function handle(ChatEnded|CallEnded $event): void
    {
        try {
            $session = $event->session;
            if (!$session) {
                return;
            }

            $channelType = ($event instanceof CallEnded) ? 'call' : 'chat';
            $channelLabel = ucfirst($channelType);

            $consumerId = (int) $session->consumer_id;
            $providerId = (int) $session->provider_id;

            $consumer = User::find($consumerId);
            $provider = User::with('astrologer')->find($providerId);

            $userName = $consumer?->name ?? 'User';
            $astrologerName = $provider?->name ?? 'Astrologer';

            // Duration calculation
            $durationSeconds = (int) ($session->duration_seconds ?? 0);
            $mins = floor($durationSeconds / 60);
            $secs = $durationSeconds % 60;
            $durationFormatted = sprintf('%02d:%02d', $mins, $secs);

            // Financial Calculations
            $amountDeducted = (float) ($session->total_cost ?? 0.00);

            $astrologerSharePct = 70.0; // Fallback default 70%
            if ($provider && $provider->astrologer) {
                try {
                    $pricingCalculator = app(PricingCalculatorService::class);
                    $pricing = $pricingCalculator->calculate($provider->astrologer, $channelType);
                    $astrologerSharePct = (float) ($pricing['astrologer_share_percentage'] ?? 70.0);
                } catch (Throwable) {
                    $astrologerSharePct = 70.0;
                }
            }
            $amountCredited = round(($amountDeducted * $astrologerSharePct) / 100, 2);

            // Fetch live wallet balances
            $userRemainingBalance = (float) (Wallet::where('user_id', $consumerId)->value('balance') ?? 0.00);
            $astrologerWalletBalance = (float) (Wallet::where('user_id', $providerId)->value('balance') ?? 0.00);

            // Determine who ended the session
            $endedById = $event->endedById ?? null;
            $endedByRole = 'system';
            $endedByName = 'System';

            if ($endedById == $consumerId) {
                $endedByRole = 'user';
                $endedByName = $userName;
            } elseif ($endedById == $providerId) {
                $endedByRole = 'astrologer';
                $endedByName = $astrologerName;
            }

            // Standardized extra data map matching frontend JSON spec
            $commonData = [
                'session_id'                => (string) $session->id,
                'channel_type'              => $channelType,
                'user_id'                   => (string) $consumerId,
                'user_name'                 => $userName,
                'astrologer_id'             => (string) $providerId,
                'astrologer_name'           => $astrologerName,
                'ended_by_id'               => (string) ($endedById ?? ''),
                'ended_by_role'             => $endedByRole,
                'ended_by_name'             => $endedByName,
                'duration_seconds'          => (string) $durationSeconds,
                'duration_formatted'        => $durationFormatted,
                'amount_deducted'           => (string) number_format($amountDeducted, 2, '.', ''),
                'amount_credited'           => (string) number_format($amountCredited, 2, '.', ''),
                'user_remaining_balance'    => (string) number_format($userRemainingBalance, 2, '.', ''),
                'astrologer_wallet_balance' => (string) number_format($astrologerWalletBalance, 2, '.', ''),
                'screen_route'              => '/session-summary',
            ];

            // 1. Send Push & In-App Notification to CONSUMER USER
            $userTitle = "{$channelLabel} Ended ✨";
            if ($endedByRole === 'user') {
                $userBody = "You ended the {$channelType} with {$astrologerName}. Duration: {$durationFormatted}. Deducted: ₹" . number_format($amountDeducted, 2) . ". Balance: ₹" . number_format($userRemainingBalance, 2) . ".";
            } elseif ($endedByRole === 'astrologer') {
                $userBody = "{$astrologerName} ended the {$channelType}. Duration: {$durationFormatted}. Deducted: ₹" . number_format($amountDeducted, 2) . ". Balance: ₹" . number_format($userRemainingBalance, 2) . ".";
            } else {
                $userBody = "Your {$channelType} with {$astrologerName} ended. Duration: {$durationFormatted}. Deducted: ₹" . number_format($amountDeducted, 2) . ". Balance: ₹" . number_format($userRemainingBalance, 2) . ".";
            }

            $userPayload = PushNotificationPayload::forSessionEnded(
                sessionId: (int) $session->id,
                channelType: $channelType,
                recipientRole: 'user',
                title: $userTitle,
                body: $userBody,
                sessionData: $commonData
            );

            NotificationService::sendToUser($consumerId, $userPayload, saveInApp: true);

            // 2. Send Push & In-App Notification to ASTROLOGER
            $astroTitle = "{$channelLabel} Ended 💰";
            if ($endedByRole === 'astrologer') {
                $astroBody = "You ended the {$channelType} with {$userName}. Duration: {$durationFormatted}. Credited: ₹" . number_format($amountCredited, 2) . ". Balance: ₹" . number_format($astrologerWalletBalance, 2) . ".";
            } elseif ($endedByRole === 'user') {
                $astroBody = "{$userName} ended the {$channelType}. Duration: {$durationFormatted}. Credited: ₹" . number_format($amountCredited, 2) . ". Balance: ₹" . number_format($astrologerWalletBalance, 2) . ".";
            } else {
                $astroBody = "Your {$channelType} with {$userName} ended. Duration: {$durationFormatted}. Credited: ₹" . number_format($amountCredited, 2) . ". Balance: ₹" . number_format($astrologerWalletBalance, 2) . ".";
            }

            $astroPayload = PushNotificationPayload::forSessionEnded(
                sessionId: (int) $session->id,
                channelType: $channelType,
                recipientRole: 'astrologer',
                title: $astroTitle,
                body: $astroBody,
                sessionData: $commonData
            );

            NotificationService::sendToUser($providerId, $astroPayload, saveInApp: true);

        } catch (Throwable $e) {
            Log::error('SendSessionEndedPushListener error: ' . $e->getMessage());
        }
    }
}
