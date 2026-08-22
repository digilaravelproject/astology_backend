<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Astrologer;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GiftController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    public function index(Request $request): JsonResponse
    {
        $gifts = Gift::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'icon_url', 'description', 'price']);

        return response()->json([
            'status' => 'success',
            'data' => ['gifts' => $gifts],
        ], 200);
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'gift_id' => 'required|integer|exists:gifts,id',
            'astrologer_id' => 'required|integer|exists:astrologers,id',
            'payment_method' => 'nullable|in:wallet,razorpay',
            'razorpay_order_id' => 'nullable|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
        ]);

        $gift = Gift::findOrFail($validated['gift_id']);
        if (!$gift->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Selected gift is not available.'], 422);
        }

        $astrologer = Astrologer::findOrFail($validated['astrologer_id']);
        $amount = $gift->price;
        $paymentMethod = $validated['payment_method'] ?? 'wallet';

        try {
            DB::beginTransaction();

            if ($paymentMethod === 'razorpay') {
                if (empty($validated['razorpay_order_id']) || empty($validated['razorpay_payment_id']) || empty($validated['razorpay_signature'])) {
                    return response()->json(['status' => 'error', 'message' => 'Razorpay payment details are required when payment_method is razorpay.'], 422);
                }

                $isValid = $this->razorpayService->verifySignature(
                    $validated['razorpay_order_id'],
                    $validated['razorpay_payment_id'],
                    $validated['razorpay_signature']
                );

                if (!$isValid) {
                    return response()->json(['status' => 'error', 'message' => 'Razorpay payment verification failed.'], 422);
                }

                $status = 'completed';
            } else {
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet || $wallet->balance < $amount) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Insufficient wallet balance. Please top-up your wallet before sending a gift.'], 422);
                }

                $balanceBefore = $wallet->balance;
                $wallet->balance -= $amount;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'transaction_type' => 'debit',
                    'amount' => $amount,
                    'status' => 'completed',
                    'payment_provider' => 'wallet',
                    'description' => 'Gift purchase',
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                ]);

                $status = 'completed';
            }

            $adminCommissionRate = (float) \App\Models\Setting::get('gift_admin_commission_percentage', 50.00);
            $astrologerSharePct = max(0, min(100, 100 - $adminCommissionRate));
            $astrologerEarning = round(($amount * $astrologerSharePct) / 100, 2);
            $adminEarning = round($amount - $astrologerEarning, 2);

            $transaction = GiftTransaction::create([
                'user_id' => $user->id,
                'astrologer_id' => $astrologer->id,
                'gift_id' => $gift->id,
                'amount' => $amount,
                'payment_provider' => $paymentMethod,
                'provider_order_id' => $validated['razorpay_order_id'] ?? null,
                'provider_payment_id' => $validated['razorpay_payment_id'] ?? null,
                'status' => $status,
                'meta' => [
                    'sender_name'                 => $user->name,
                    'astrologer_name'             => optional($astrologer->user)->name,
                    'gift_title'                  => $gift->title,
                    'gift_total_amount'           => (float) $amount,
                    'astrologer_earning'          => (float) $astrologerEarning,
                    'admin_earning'               => (float) $adminEarning,
                    'astrologer_share_percentage' => (float) $astrologerSharePct,
                    'admin_commission_percentage' => (float) $adminCommissionRate,
                ],
            ]);

            if ($astrologer->user) {
                $this->creditAstrologerWallet($astrologer->user->id, $astrologerEarning, $transaction->id, [
                    'gift_total_amount'           => (float) $amount,
                    'astrologer_earning'          => (float) $astrologerEarning,
                    'astrologer_share_percentage' => (float) $astrologerSharePct,
                    'admin_commission_percentage' => (float) $adminCommissionRate,
                ]);

                // Dispatch Push & In-App Notification to Astrologer
                try {
                    \App\Services\NotificationHelper::send(
                        userId: $astrologer->user->id,
                        title: 'Gift Received! 🎁',
                        body: "{$user->name} sent you a {$gift->title} worth ₹" . number_format($amount, 2) . " (Credited: ₹" . number_format($astrologerEarning, 2) . ")!",
                        meta: [
                            'type'         => 'gift',
                            'gift_id'      => (string) $gift->id,
                            'sender_id'    => (string) $user->id,
                            'sender_name'  => $user->name,
                            'amount'       => (string) $amount,
                            'credited'     => (string) $astrologerEarning,
                            'screen_route' => '/wallet',
                        ]
                    );
                } catch (\Throwable $ne) {
                    Log::error('Failed to notify astrologer about gift: ' . $ne->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Gift sent successfully.',
                'data' => ['transaction' => $transaction],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Send gift failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send gift.'], 500);
        }
    }

    protected function creditAstrologerWallet(int $userId, float $amount, int $transactionId, array $meta = []): void
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            $wallet = Wallet::create(['user_id' => $userId, 'balance' => 0]);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
        }

        $balanceBefore = (float) $wallet->balance;
        $wallet->balance += $amount;
        $wallet->save();

        WalletTransaction::create([
            'wallet_id'        => $wallet->id,
            'transaction_type' => 'credit',
            'amount'           => $amount,
            'status'           => 'completed',
            'payment_provider' => 'gift',
            'description'      => 'Gift received',
            'balance_before'   => $balanceBefore,
            'balance_after'    => (float) $wallet->balance,
            'reference_type'   => GiftTransaction::class,
            'reference_id'     => $transactionId,
            'meta'             => $meta,
        ]);
    }

    public function astrologerGifts(Request $request, $astrologerId): JsonResponse
    {
        $astrologer = Astrologer::find($astrologerId);
        if (!$astrologer) {
            return response()->json(['status' => 'error', 'message' => 'Astrologer not found.'], 404);
        }

        $transactions = GiftTransaction::with(['gift', 'sender'])
            ->where('astrologer_id', $astrologer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'astrologer_id' => $astrologer->id,
                'astrologer_name' => optional($astrologer->user)->name,
                'gifts' => $transactions,
            ],
        ], 200);
    }
}
