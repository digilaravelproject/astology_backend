<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AstrologerWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AstrologerWalletController extends Controller
{
    protected $walletService;

    public function __construct(AstrologerWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet balance and key metrics for the authenticated astrologer.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $summary = $this->walletService->getWalletSummary($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $summary
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch wallet summary.'
            ], 500);
        }
    }

    /**
     * Get earning history for the astrologer with optional filters (today, weekly, monthly).
     */
    public function earnings(Request $request): JsonResponse
    {
        try {
            $earnings = $this->walletService->getEarningsHistory($request->user(), $request->input('filter'));
            return response()->json([
                'status' => 'success',
                'data' => $earnings
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch earning history.'
            ], 500);
        }
    }

    /**
     * Get withdrawal history for the astrologer.
     */
    public function withdrawals(Request $request): JsonResponse
    {
        try {
            $withdrawals = $this->walletService->getWithdrawalHistory($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $withdrawals
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch withdrawal history.'
            ], 500);
        }
    }

    /**
     * Request a withdrawal to a selected bank account.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_account_id' => ['required', 'integer'],
        ]);

        try {
            $result = $this->walletService->requestWithdrawal(
                $request->user(),
                (float)$request->input('amount'),
                (int)$request->input('bank_account_id')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal request submitted successfully.',
                'data' => $result
            ], 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            // Handle expected validation errors
            if ($code === 422) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?? 'Failed to submit withdrawal request.'
            ], 500);
        }
    }

    /**
     * Get weekly ranking for astrologers based on current week's earnings.
     */
    public function weeklyRankings(Request $request): JsonResponse
    {
        try {
            $rankings = $this->walletService->getWeeklyRankings($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $rankings
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch weekly rankings.'
            ], 500);
        }
    }

    /**
     * Get monthly invoice summary list and stats for the astrologer.
     */
    public function invoices(Request $request): JsonResponse
    {
        try {
            $invoices = $this->walletService->getInvoicesSummary($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $invoices
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch invoice summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download monthly invoice as text/plain.
     */
    public function downloadInvoice(Request $request, $year, $month)
    {
        try {
            $user = $request->user();
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
            }

            // Fetch transactions for that year-month
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $transactions = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transaction_type', 'credit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $totalEarnings = $transactions->sum('amount');

            // Generate simple text invoice
            $invoiceText = "========================================\n";
            $invoiceText .= "            EARNINGS INVOICE            \n";
            $invoiceText .= "========================================\n";
            $invoiceText .= "Astrologer Name: " . $user->name . "\n";
            $invoiceText .= "Email: " . $user->email . "\n";
            $invoiceText .= "Period: " . $startDate->format('F Y') . "\n";
            $invoiceText .= "Generated At: " . now()->toDateTimeString() . "\n";
            $invoiceText .= "----------------------------------------\n";
            $invoiceText .= "Transaction ID | Date | Description | Amount\n";
            $invoiceText .= "----------------------------------------\n";
            foreach ($transactions as $tx) {
                $invoiceText .= sprintf(
                    "%s | %s | %s | INR %s\n",
                    $tx->id,
                    $tx->created_at->toDateString(),
                    substr($tx->description, 0, 20),
                    $tx->amount
                );
            }
            $invoiceText .= "----------------------------------------\n";
            $invoiceText .= "Gross Earnings: INR " . number_format($totalEarnings, 2) . "\n";
            $invoiceText .= "Net Payable: INR " . number_format($totalEarnings, 2) . "\n";
            $invoiceText .= "========================================\n";

            $fileName = "invoice_{$year}_{$month}.txt";

            return response($invoiceText, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download invoice: ' . $e->getMessage()
            ], 500);
        }
    }
}
