<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\SuperChat;
use App\Models\Wallet;
use App\Events\SuperChatReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SuperChatService
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function processSuperChat($session, $user, int $giftId, ?string $message): array
    {
        $gift = Gift::findOrFail($giftId);

        if (!$gift->is_active) {
            throw new \RuntimeException('Selected gift is not available.', 422);
        }

        $amount = (float) $gift->price;
        $astrologerUserId = $session->astrologer->user_id;
        $giftMessage = "[Gift: {$gift->title}]" . ($message ? ' ' . $message : '');

        $superChat = DB::transaction(function () use ($session, $user, $amount, $astrologerUserId, $giftMessage) {
            $firstUserId = min($user->id, $astrologerUserId);
            $secondUserId = max($user->id, $astrologerUserId);

            if ($firstUserId === $secondUserId) {
                $userWallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                $astrologerWallet = $userWallet;
            } else {
                $firstWallet = Wallet::where('user_id', $firstUserId)->lockForUpdate()->first();
                $secondWallet = Wallet::where('user_id', $secondUserId)->lockForUpdate()->first();

                $userWallet = $user->id === $firstUserId ? $firstWallet : $secondWallet;
                $astrologerWallet = $user->id === $firstUserId ? $secondWallet : $firstWallet;
            }

            $superChat = SuperChat::create([
                'live_session_id'    => $session->id,
                'user_id'            => $user->id,
                'astrologer_id'      => $session->astrologer_id,
                'amount'             => $amount,
                'message'            => $giftMessage,
                'transaction_status' => 'pending',
            ]);

            $txns = $this->walletService->transferForSuperChat($user->id, $astrologerUserId, $amount, $superChat);

            $superChat->update([
                'transaction_status'  => 'completed',
                'wallet_transaction_id' => $txns['debit']->id,
            ]);

            return $superChat->fresh();
        }, 3);

        try {
            broadcast(new SuperChatReceived($session->id, [
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_avatar' => \App\Helpers\MediaHelper::getUrl($user->profile_photo),
                'amount'     => $amount,
                'message'    => $superChat->message ?? '',
                'gift'       => [
                    'id'       => $gift->id,
                    'title'    => $gift->title,
                    'icon_url' => $gift->icon_url,
                ],
                'created_at' => $superChat->created_at->toISOString(),
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast SuperChatReceived', ['error' => $e->getMessage()]);
        }

        return [
            'superChat' => $superChat,
            'amount' => $amount,
            'gift' => $gift,
        ];
    }
}
