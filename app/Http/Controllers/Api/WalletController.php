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

        // Auto-reconcile any pending Razorpay top-ups inline so wallet is immediately updated
        $this->autoReconcilePending($wallet);
        $wallet->refresh();

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

        Log::info('📥 [Razorpay Create Topup] User requesting wallet top-up', [
            'user_id' => $user->id,
            'amount_requested' => $request->input('amount'),
            'ip' => $request->ip(),
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
                Log::error('❌ [Razorpay Create Topup] Failed to create Razorpay order', [
                    'user_id' => $user->id,
                    'error' => $razorpayResult['message'] ?? 'Unknown error',
                ]);
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

            Log::info('✅ [Razorpay Create Topup] Order created successfully', [
                'user_id' => $user->id,
                'order_id' => $razorpayOrder['id'],
                'amount_paise' => $amountInPaise,
                'base_amount' => $baseAmount,
                'total_payable' => $totalPayable,
                'transaction_id' => $transaction->id,
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

        } catch (\Throwable $e) {
            Log::error('❌ [Razorpay Create Topup] Exception: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Failed to initiate top-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify a Razorpay payment, credit base amount to wallet, and issue tax invoice.
     */
    public function verifyTopup(Request $request): JsonResponse
    {
        Log::info('📥 [Razorpay Verify Topup] Incoming Request', [
            'url'         => $request->fullUrl(),
            'method'      => $request->method(),
            'user_id'     => $request->user()?->id,
            'ip'          => $request->ip(),
            'all_input'   => $request->all(),
            'bearer_sent' => $request->bearerToken() ? 'YES (token present)' : 'NO (missing)',
        ]);

        $user = $request->user();
        if (!$user) {
            Log::warning('⚠️ [Razorpay Verify Topup] Rejected: Unauthenticated user');
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        // Support multiple naming conventions from different Flutter/Frontend SDKs
        $orderId = $request->input('razorpay_order_id') 
            ?? $request->input('order_id') 
            ?? $request->input('razorpayOrderId') 
            ?? $request->input('orderId');

        $paymentId = $request->input('razorpay_payment_id') 
            ?? $request->input('payment_id') 
            ?? $request->input('razorpayPaymentId') 
            ?? $request->input('paymentId');

        $signature = $request->input('razorpay_signature') 
            ?? $request->input('signature') 
            ?? $request->input('razorpaySignature');

        Log::info('🔍 [Razorpay Verify Topup] Parsed Parameters', [
            'user_id'    => $user->id,
            'order_id'   => $orderId,
            'payment_id' => $paymentId,
            'signature'  => $signature ? (substr($signature, 0, 10) . '...') : null,
        ]);

        if (empty($orderId) || empty($paymentId)) {
            Log::error('❌ [Razorpay Verify Topup] Missing required parameters', [
                'has_order_id'   => !empty($orderId),
                'has_payment_id' => !empty($paymentId),
                'has_signature'  => !empty($signature),
                'raw_input'      => $request->all(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Missing required payment parameters (order_id and payment_id are required).',
            ], 422);
        }

        try {
            $isVerified = false;

            // 1. First attempt: Verify cryptographic signature if present
            if (!empty($signature)) {
                $isVerified = $this->razorpayService->verifySignature($orderId, $paymentId, $signature);
                Log::info('🔐 [Razorpay Verify Topup] Signature Verification Result', [
                    'order_id'   => $orderId,
                    'payment_id' => $paymentId,
                    'valid'      => $isVerified,
                ]);
            }

            // 2. Second attempt (Fallback): Query Razorpay API directly if signature failed or wasn't sent
            if (!$isVerified) {
                Log::warning('⚠️ [Razorpay Verify Topup] Signature missing or failed. Checking Razorpay API directly for payment: ' . $paymentId);
                $paymentCheck = $this->razorpayService->getPayment($paymentId);
                Log::info('📡 [Razorpay Verify Topup] Direct Razorpay API Response', [
                    'paymentCheck' => $paymentCheck,
                ]);

                if (
                    ($paymentCheck['status'] ?? '') === 'success' &&
                    in_array(($paymentCheck['data']['status'] ?? ''), ['captured', 'authorized']) &&
                    (($paymentCheck['data']['order_id'] ?? '') === $orderId)
                ) {
                    Log::info('✅ [Razorpay Verify Topup] Payment confirmed directly via Razorpay API');
                    $isVerified = true;
                }
            }

            if (!$isVerified) {
                Log::error('❌ [Razorpay Verify Topup] Payment verification completely failed for order: ' . $orderId);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment signature verification failed.',
                ], 422);
            }

            // Locate the pending transaction (or check if already completed)
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
            $transaction = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('provider_order_id', $orderId)
                ->first();

            // Fallback: search across all wallets if user re-registered or session differed
            if (!$transaction) {
                $transaction = WalletTransaction::where('provider_order_id', $orderId)->first();
            }

            if (!$transaction) {
                Log::error('❌ [Razorpay Verify Topup] Transaction record not found for order: ' . $orderId);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Transaction record not found for order: ' . $orderId,
                ], 404);
            }

            // Perform atomic credit
            $result = $this->creditPendingTransaction($transaction, $paymentId, $signature, 'verify_api');

            Log::info('🎉 [Razorpay Verify Topup] Verification Complete', [
                'user_id'           => $user->id,
                'order_id'          => $orderId,
                'payment_id'        => $paymentId,
                'already_processed' => $result['already_processed'],
                'new_balance'       => $result['wallet']->balance,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => $result['already_processed']
                    ? 'Payment already verified and wallet credited.'
                    : 'Top-up verified and wallet credited successfully.',
                'data'    => [
                    'wallet'      => $result['wallet']->fresh(),
                    'transaction' => $result['transaction'],
                    'invoice_url' => url("/api/v1/user/wallet/transactions/{$result['transaction']->id}/invoice"),
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('💥 [Razorpay Verify Topup] Exception: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atomically credit a pending wallet transaction, issue invoice and dispatch notifications.
     */
    public function creditPendingTransaction(
        WalletTransaction $transaction,
        string $paymentId,
        ?string $signature = null,
        string $source = 'verify'
    ): array {
        $taxService = app(\App\Services\WalletTaxService::class);

        return DB::transaction(function () use ($transaction, $paymentId, $signature, $source, $taxService) {
            $lockedTx = WalletTransaction::where('id', $transaction->id)->lockForUpdate()->first();
            if (!$lockedTx) {
                throw new \Exception("Transaction {$transaction->id} not found.");
            }

            $wallet = Wallet::where('id', $lockedTx->wallet_id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create(['user_id' => $lockedTx->wallet?->user_id ?? 0, 'balance' => 0]);
            }

            // Idempotency: If already completed, do not credit twice
            if ($lockedTx->status === 'completed') {
                return [
                    'wallet' => $wallet,
                    'transaction' => $lockedTx,
                    'already_processed' => true,
                ];
            }

            // Generate sequential Tax Invoice Number
            $invoiceNumber = $taxService->generateInvoiceNumber('REC', $lockedTx->id);

            $lockedTx->balance_before = $wallet->balance;
            $lockedTx->balance_after = $wallet->balance + $lockedTx->amount;
            $lockedTx->provider_payment_id = $paymentId;
            $lockedTx->status = 'completed';
            $lockedTx->invoice_number = $invoiceNumber;
            $lockedTx->meta = array_merge($lockedTx->meta ?? [], [
                'verified_at'     => now()->toDateTimeString(),
                'verified_source' => $source,
                'signature'       => $signature,
            ]);
            $lockedTx->save();

            // Credit wallet balance with the base recharge amount strictly
            $wallet->balance += $lockedTx->amount;
            $wallet->save();

            Log::info("💰 [Wallet Recharged] Balance updated for User #{$wallet->user_id}", [
                'user_id'        => $wallet->user_id,
                'credit_amount'  => $lockedTx->amount,
                'total_paid'     => $lockedTx->total_amount,
                'new_balance'    => $wallet->balance,
                'invoice_number' => $invoiceNumber,
                'source'         => $source,
                'payment_id'     => $paymentId,
            ]);

            // Dispatch Push & In-App Notification
            try {
                $walletPayload = \App\Services\Notification\PushNotificationPayload::forSystem(
                    title: 'Wallet Recharged! 💳',
                    body: '₹' . number_format($lockedTx->amount, 2) . ' added to your wallet. New Balance: ₹' . number_format($wallet->balance, 2),
                    type: 'wallet',
                    referenceId: (string) $lockedTx->id,
                    extra: [
                        'amount'          => (string) $lockedTx->amount,
                        'new_balance'     => (string) $wallet->balance,
                        'invoice_number'  => (string) $invoiceNumber,
                        'screen_route'    => '/wallet',
                    ]
                );
                \App\Services\NotificationService::sendToUser($wallet->user_id, $walletPayload, saveInApp: true);
            } catch (\Throwable $ne) {
                Log::error('Wallet topup notification failed: ' . $ne->getMessage());
            }

            return [
                'wallet' => $wallet,
                'transaction' => $lockedTx,
                'already_processed' => false,
            ];
        }, 3);
    }

    /**
     * Handle incoming asynchronous Razorpay Webhooks (order.paid, payment.captured)
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? 'unknown';

        Log::info("🔔 [Razorpay Webhook] Received webhook event: {$event}", [
            'event'   => $event,
            'payload' => $payload,
        ]);

        if (!in_array($event, ['payment.captured', 'order.paid'])) {
            return response()->json(['status' => 'ignored', 'message' => 'Event not handled.'], 200);
        }

        $paymentEntity = $payload['payload']['payment']['entity'] ?? null;
        if (!$paymentEntity) {
            return response()->json(['status' => 'error', 'message' => 'Missing payment entity.'], 400);
        }

        $orderId = $paymentEntity['order_id'] ?? null;
        $paymentId = $paymentEntity['id'] ?? null;

        if (!$orderId || !$paymentId) {
            Log::warning('⚠️ [Razorpay Webhook] Missing order_id or payment_id in webhook', [
                'order_id'   => $orderId,
                'payment_id' => $paymentId,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Missing order_id or payment_id.'], 400);
        }

        $transaction = WalletTransaction::where('provider_order_id', $orderId)->first();
        if (!$transaction) {
            Log::warning("⚠️ [Razorpay Webhook] No matching transaction found for order: {$orderId}");
            return response()->json(['status' => 'not_found', 'message' => 'Transaction not found.'], 200);
        }

        if ($transaction->status === 'completed') {
            Log::info("ℹ️ [Razorpay Webhook] Order {$orderId} was already completed.");
            return response()->json(['status' => 'already_processed'], 200);
        }

        $result = $this->creditPendingTransaction($transaction, $paymentId, null, 'webhook');
        Log::info("✅ [Razorpay Webhook] Successfully credited order {$orderId} via webhook.");

        return response()->json(['status' => 'success', 'data' => $result], 200);
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

        // Auto-reconcile any pending Razorpay top-ups inline so transactions history is up to date
        $this->autoReconcilePending($wallet);
        $wallet->refresh();

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

    /**
     * Auto-check and reconcile any recent pending Razorpay recharges for this wallet directly from Razorpay API.
     */
    protected function autoReconcilePending(Wallet $wallet): void
    {
        try {
            $pendingTx = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('status', 'pending')
                ->where('payment_provider', 'razorpay')
                ->whereNotNull('provider_order_id')
                ->where('created_at', '>=', now()->subDays(2))
                ->get();

            if ($pendingTx->isEmpty()) {
                return;
            }

            foreach ($pendingTx as $tx) {
                $orderId = $tx->provider_order_id;
                $orderResult = $this->razorpayService->getOrder($orderId);

                if (($orderResult['status'] ?? '') === 'success') {
                    $orderData = $orderResult['data'];
                    $orderStatus = $orderData['status'] ?? '';
                    $amountPaid = (int) ($orderData['amount_paid'] ?? 0);

                    if ($orderStatus === 'paid' || $amountPaid > 0) {
                        $paymentId = null;
                        try {
                            $payments = $this->razorpayService->getApi()->order->fetch($orderId)->payments();
                            if (isset($payments->items) && is_array($payments->items)) {
                                foreach ($payments->items as $p) {
                                    if (($p->status ?? '') === 'captured') {
                                        $paymentId = $p->id;
                                        break;
                                    }
                                }
                                if (!$paymentId && !empty($payments->items)) {
                                    $paymentId = $payments->items[0]->id ?? null;
                                }
                            }
                        } catch (\Throwable $pe) {
                            Log::warning("Could not fetch payments for {$orderId}: " . $pe->getMessage());
                        }

                        $paymentId = $paymentId ?? ('pay_auto_' . time());

                        $this->creditPendingTransaction($tx, $paymentId, null, 'auto_reconcile');
                        Log::info("⚡ [Auto-Reconcile] Successfully credited pending order {$orderId} for User #{$wallet->user_id}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('⚠️ [Auto-Reconcile] Check encountered an error: ' . $e->getMessage());
        }
    }
}
