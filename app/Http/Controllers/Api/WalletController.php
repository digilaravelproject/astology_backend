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
     * Create a top-up order (Razorpay) for adding funds to wallet with GST calculation.
     */
    public function createTopup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $minRecharge = (float) \App\Models\Setting::get('min_wallet_recharge', 1.00);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:' . ($minRecharge > 0 ? $minRecharge : 1), 'max:100000'],
        ], [
            'amount.min' => 'The minimum recharge amount is ₹' . number_format($minRecharge, 2) . '.',
        ]);

        try {
            $baseAmount = (float)$request->input('amount');
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            $taxService = app(\App\Services\WalletTaxService::class);
            $taxBreakdown = $taxService->calculateRechargeTax($baseAmount);

            // Total amount payable by user including GST
            $totalPayable = $taxBreakdown['total_payable'];
            $amountInPaise = (int) round($totalPayable * 100);

            $razorpayResult = $this->razorpayService->createOrder(
                $amountInPaise,
                'INR',
                'topup_' . $user->id . '_' . time(),
                [
                    'user_id' => (string)$user->id,
                    'base_amount' => (string)$baseAmount,
                    'gst_amount' => (string)$taxBreakdown['gst_amount'],
                    'total_payable' => (string)$totalPayable,
                    'description' => 'Wallet top-up with GST',
                ]
            );

            if ($razorpayResult['status'] !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => $razorpayResult['message'] ?? 'Failed to create Razorpay order.',
                ], 422);
            }

            $razorpayOrder = $razorpayResult['data'];

            // Save pending transaction: 'amount' is strictly the base amount added to ledger
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'credit',
                'amount' => $baseAmount,
                'base_amount' => $baseAmount,
                'gst_percent' => $taxBreakdown['gst_percent'],
                'gst_amount' => $taxBreakdown['gst_amount'],
                'total_amount' => $totalPayable,
                'status' => 'pending',
                'payment_provider' => 'razorpay',
                'provider_order_id' => $razorpayOrder['id'],
                'description' => 'Wallet top-up (pending payment)',
                'meta' => [
                    'tax_breakdown' => $taxBreakdown,
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up order created. Proceed to payment.',
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'pricing_breakdown' => [
                        'base_amount' => $taxBreakdown['base_amount'],
                        'gst_enabled' => $taxBreakdown['gst_enabled'],
                        'gst_percent' => $taxBreakdown['gst_percent'],
                        'gst_amount' => $taxBreakdown['gst_amount'],
                        'total_payable' => $taxBreakdown['total_payable'],
                        'wallet_credit_amount' => $taxBreakdown['credit_to_wallet'],
                    ],
                    'razorpay_order' => [
                        'id' => $razorpayOrder['id'],
                        'amount' => $razorpayOrder['amount'],
                        'currency' => $razorpayOrder['currency'],
                        'key_id' => config('razorpay.key_id'),
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create topup error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to initiate top-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify a Razorpay payment, credit base amount to wallet, and issue tax invoice.
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
            // Verify Razorpay signature
            $isSignatureValid = $this->razorpayService->verifySignature(
                $request->input('razorpay_order_id'),
                $request->input('razorpay_payment_id'),
                $request->input('razorpay_signature')
            );

            if (!$isSignatureValid) {
                return response()->json(['status' => 'error', 'message' => 'Payment signature verification failed.'], 422);
            }

            $taxService = app(\App\Services\WalletTaxService::class);

            // Execute in DB Transaction with deadlock retry (3 attempts)
            $result = DB::transaction(function () use ($user, $request, $taxService) {
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet) {
                    $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);
                }

                $transaction = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('provider_order_id', $request->input('razorpay_order_id'))
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                // Idempotency: If transaction is already completed, return existing record
                if (!$transaction) {
                    $completed = WalletTransaction::where('wallet_id', $wallet->id)
                        ->where('provider_order_id', $request->input('razorpay_order_id'))
                        ->where('status', 'completed')
                        ->first();

                    if ($completed) {
                        return [
                            'wallet' => $wallet,
                            'transaction' => $completed,
                            'already_processed' => true,
                        ];
                    }

                    throw new Exception('Pending top-up transaction already processed or not found.', 404);
                }

                // Generate sequential Tax Invoice Number
                $invoiceNumber = $taxService->generateInvoiceNumber('REC', $transaction->id);

                // Set balance before and after (credit base amount only)
                $transaction->balance_before = $wallet->balance;
                $transaction->balance_after = $wallet->balance + $transaction->amount;
                $transaction->provider_payment_id = $request->input('razorpay_payment_id');
                $transaction->status = 'completed';
                $transaction->invoice_number = $invoiceNumber;
                $transaction->meta = array_merge($transaction->meta ?? [], [
                    'verified_at' => now()->toDateTimeString(),
                    'signature' => $request->input('razorpay_signature'),
                ]);
                $transaction->save();

                // Credit wallet balance with the base recharge amount strictly
                $wallet->balance += $transaction->amount;
                $wallet->save();

                return [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'already_processed' => false,
                ];
            }, 3);

            $wallet = $result['wallet'];
            $transaction = $result['transaction'];

            if (!$result['already_processed']) {
                Log::info('Wallet credited with base amount', [
                    'user_id' => $wallet->user_id,
                    'base_amount' => $transaction->amount,
                    'total_paid' => $transaction->total_amount,
                    'gst_amount' => $transaction->gst_amount,
                    'new_balance' => $wallet->balance,
                    'invoice_number' => $transaction->invoice_number,
                ]);

                // Dispatch Push & In-App Notification
                try {
                    $walletPayload = \App\Services\Notification\PushNotificationPayload::forSystem(
                        title: 'Wallet Recharged! 💳',
                        body: '₹' . number_format($transaction->amount, 2) . ' added to your wallet. New Balance: ₹' . number_format($wallet->balance, 2),
                        type: 'wallet',
                        referenceId: (string) $transaction->id,
                        extra: [
                            'amount'          => (string) $transaction->amount,
                            'new_balance'     => (string) $wallet->balance,
                            'invoice_number'  => (string) $transaction->invoice_number,
                            'screen_route'    => '/wallet',
                        ]
                    );
                    \App\Services\NotificationService::sendToUser($user->id, $walletPayload, saveInApp: true);
                } catch (\Throwable $ne) {
                    Log::error('Wallet topup notification failed: ' . $ne->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up verified and wallet credited.',
                'data' => [
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'invoice_url' => url("/api/v1/user/wallet/transactions/{$transaction->id}/invoice"),
                ],
            ], 200);

        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 404) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
            }
            Log::error('Verify topup error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to verify payment.'], 500);
        }
    }

    /**
     * Download itemized Tax Invoice PDF for a user recharge transaction.
     */
    public function downloadInvoice(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }

            $wallet = Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
            }

            $transaction = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('id', $id)
                ->first();

            if (!$transaction) {
                return response()->json(['status' => 'error', 'message' => 'Transaction not found.'], 404);
            }

            $taxService = app(\App\Services\WalletTaxService::class);
            return $taxService->downloadInvoicePdf($transaction);
        } catch (Exception $e) {
            Log::error('Download invoice error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to download invoice.'], 500);
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
     * FIXED: Proper ownership verification to prevent IDOR
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

        // Verify the transaction belongs to the authenticated user's wallet
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
