<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }
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
     * This endpoint creates a Razorpay order that must be verified with payment details.
     */
    public function createTopup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
        ]);

        try {
            DB::beginTransaction();

            $amount = (float)$request->input('amount');
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // Create Razorpay order
            $amountInPaise = (int)($amount * 100);
            $razorpayResult = $this->razorpayService->createOrder(
                $amountInPaise,
                'INR',
                'topup_' . $user->id . '_' . time(),
                [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'description' => 'Wallet top-up',
                ]
            );

            if ($razorpayResult['status'] !== 'success') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $razorpayResult['message'] ?? 'Failed to create Razorpay order.',
                ], 422);
            }

            $razorpayOrder = $razorpayResult['data'];

            // Create transaction record
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'status' => 'pending',
                'payment_provider' => 'razorpay',
                'provider_order_id' => $razorpayOrder['id'],
                'description' => 'Wallet top-up (pending payment)',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up order created. Proceed to payment.',
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'razorpay_order' => [
                        'id' => $razorpayOrder['id'],
                        'amount' => $razorpayOrder['amount'],
                        'currency' => $razorpayOrder['currency'],
                        'key_id' => config('razorpay.key_id'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create topup error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to initiate top-up.'], 500);
        }
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
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // Verify Razorpay signature
            $isSignatureValid = $this->razorpayService->verifySignature(
                $request->input('razorpay_order_id'),
                $request->input('razorpay_payment_id'),
                $request->input('razorpay_signature')
            );

            if (!$isSignatureValid) {
                return response()->json(['status' => 'error', 'message' => 'Payment signature verification failed.'], 422);
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);
            }

            $transaction = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('provider_order_id', $request->input('razorpay_order_id'))
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Pending top-up transaction already processed or not found.'], 404);
            }

            // Mark transaction as completed
            $transaction->provider_payment_id = $request->input('razorpay_payment_id');
            $transaction->status = 'completed';
            $transaction->meta = [
                'verified_at' => now()->toDateTimeString(),
                'signature' => $request->input('razorpay_signature'),
            ];
            $transaction->save();

            // Credit wallet balance
            $wallet->balance += $transaction->amount;
            $wallet->save();

            DB::commit();

            Log::info('Wallet credited', [
                'user_id' => $wallet->user_id,
                'amount' => $transaction->amount,
                'new_balance' => $wallet->balance,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up verified and wallet credited.',
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Verify topup error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to verify payment.'], 500);
        }
    }

    /**
     * List wallet transactions for authenticated user.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('status', '!=', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                // Remove text inside parentheses () including brackets
                if (!empty($transaction->description)) {
                    $transaction->description = trim(
                        preg_replace('/\s*\(.*?\)/', '', $transaction->description)
                    );
                }
                return $transaction;
            });

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
