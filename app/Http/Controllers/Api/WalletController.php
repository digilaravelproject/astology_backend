<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /**
     * Get authenticated user's wallet and balance.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => $wallet,
            ],
        ], 200);
    }

    /**
     * Create a top-up order (Razorpay) for adding funds to wallet.
     *
     * This endpoint does not complete the top-up; it creates a pending transaction.
     */
    public function createTopup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // In a real integration, you would create an order with Razorpay here.
        // We'll generate a fake order id for demonstration.
        $providerOrderId = 'razorpay_order_' . Str::random(10);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'credit',
            'amount' => $request->input('amount'),
            'status' => 'pending',
            'payment_provider' => 'razorpay',
            'provider_order_id' => $providerOrderId,
            'description' => 'Wallet top-up (pending payment)',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Top-up order created.',
            'data' => [
                'wallet' => $wallet,
                'transaction' => $transaction,
            ],
        ], 201);
    }

    /**
     * Verify a Razorpay payment and credit wallet.
     */
    public function verifyTopup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'provider_order_id' => ['required', 'string'],
            'provider_payment_id' => ['required', 'string'],
            // real integration should verify `signature`
            'signature' => ['required', 'string'],
        ]);

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $transaction = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('provider_order_id', $request->input('provider_order_id'))
            ->where('payment_provider', 'razorpay')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Pending top-up transaction not found.'], 404);
        }

        // NOTE: In a real integration you must verify the signature using Razorpay secret.
        // Here we accept it as-is and mark payment completed.
        $transaction->provider_payment_id = $request->input('provider_payment_id');
        $transaction->status = 'completed';
        $transaction->meta = [
            'verified_at' => now()->toDateTimeString(),
            'signature' => $request->input('signature'),
        ];
        $transaction->save();

        DB::transaction(function () use ($wallet, $transaction) {
            $wallet->balance = $wallet->balance + $transaction->amount;
            $wallet->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Top-up verified and wallet credited.',
            'data' => [
                'wallet' => $wallet,
                'transaction' => $transaction,
            ],
        ], 200);
    }

    /**
     * List wallet transactions for authenticated user.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => $wallet,
                'transactions' => $transactions,
            ],
        ], 200);
    }

    /**
     * Get wallet transaction detail by ID.
     */
    public function transactionDetail(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
        }

        $transaction = WalletTransaction::where('wallet_id', $wallet->id)->find($id);
        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => $wallet,
                'transaction' => $transaction,
            ],
        ], 200);
    }
}
