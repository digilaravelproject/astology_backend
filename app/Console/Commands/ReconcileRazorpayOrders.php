<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\WalletController;
use App\Models\WalletTransaction;
use App\Services\RazorpayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileRazorpayOrders extends Command
{
    protected $signature = 'razorpay:reconcile {--order= : Specific Razorpay Order ID to reconcile}';
    protected $description = 'Reconcile pending Razorpay wallet top-ups directly with Razorpay API';

    public function handle(RazorpayService $razorpayService, WalletController $walletController)
    {
        $specificOrder = $this->option('order');

        $query = WalletTransaction::where('status', 'pending')
            ->where('payment_provider', 'razorpay')
            ->whereNotNull('provider_order_id');

        if ($specificOrder) {
            $query->where('provider_order_id', $specificOrder);
        }

        $pendingTransactions = $query->get();

        if ($pendingTransactions->isEmpty()) {
            $this->info('No pending Razorpay transactions found to reconcile.');
            return 0;
        }

        $this->info("Found {$pendingTransactions->count()} pending transaction(s). Checking with Razorpay API...");

        foreach ($pendingTransactions as $tx) {
            $orderId = $tx->provider_order_id;
            $this->line("Checking Order: <comment>{$orderId}</comment> (Amount: ₹{$tx->amount})...");

            try {
                $orderResult = $razorpayService->getOrder($orderId);
                if (($orderResult['status'] ?? '') !== 'success') {
                    $this->error("  -> Could not fetch order {$orderId} from Razorpay: " . ($orderResult['message'] ?? 'Unknown error'));
                    continue;
                }

                $orderData = $orderResult['data'];
                $orderStatus = $orderData['status'] ?? 'unknown';
                $amountPaid = (int) ($orderData['amount_paid'] ?? 0);

                $this->line("  -> Razorpay Status: <info>{$orderStatus}</info>, Amount Paid: ₹" . ($amountPaid / 100));

                if ($orderStatus === 'paid' || $amountPaid > 0) {
                    // Fetch payment ID from Razorpay API
                    $payments = $razorpayService->getApi()->order->fetch($orderId)->payments();
                    $paymentId = null;
                    if (isset($payments->items) && is_array($payments->items)) {
                        foreach ($payments->items as $paymentItem) {
                            if (($paymentItem->status ?? '') === 'captured') {
                                $paymentId = $paymentItem->id;
                                break;
                            }
                        }
                        if (!$paymentId && !empty($payments->items)) {
                            $paymentId = $payments->items[0]->id ?? null;
                        }
                    }

                    $paymentId = $paymentId ?? ('pay_recon_' . time());

                    $result = $walletController->creditPendingTransaction($tx, $paymentId, null, 'artisan_reconcile');
                    $wallet = $result['wallet'];

                    $this->info("  -> [SUCCESS] Credited ₹{$tx->amount} to User #{$wallet->user_id}! New Wallet Balance: ₹{$wallet->balance}");
                } else {
                    $this->warn("  -> Order {$orderId} has NOT been marked paid on Razorpay (Status: {$orderStatus}). Skipping.");
                }
            } catch (\Throwable $e) {
                $this->error("  -> Error reconciling order {$orderId}: " . $e->getMessage());
                Log::error("Reconcile error for order {$orderId}: " . $e->getMessage());
            }
        }

        $this->info('Reconciliation finished.');
        return 0;
    }
}
