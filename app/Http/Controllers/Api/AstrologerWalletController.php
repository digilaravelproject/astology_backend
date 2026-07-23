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
     * Download monthly invoice as PDF.
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
            $monthName = $startDate->format('F Y');

            // Build dynamic styled HTML template
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - ' . $monthName . '</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; margin: 15px; font-size: 14px; }
        .invoice-box { padding: 20px; border: 1px solid #eee; }
        .header-table, .info-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-title { font-size: 28px; line-height: 35px; color: #ff5722; font-weight: bold; }
        .text-right { text-align: right; }
        .heading-row { background: #fbe9e7; border-bottom: 1px solid #ddd; font-weight: bold; color: #d84315; }
        .heading-row td { padding: 10px; }
        .item-row td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-row td { padding: 10px; text-align: right; font-weight: bold; font-size: 15px; }
        .footer { text-align: center; margin-top: 50px; font-size: 11px; color: #888; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td class="header-title">SURYAPATH KUNDLI</td>
                <td class="text-right">
                    <strong>Invoice #:</strong> INV-' . $year . '-' . $month . '<br>
                    <strong>Date:</strong> ' . now()->toDateString() . '<br>
                    <strong>Billing Period:</strong> ' . $monthName . '
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Platform Operator:</strong><br>
                    Suryapath Kundli Team<br>
                    support@suryapathkundli.com<br>
                    https://suryapathkundli.com
                </td>
                <td class="text-right" style="width: 50%;">
                    <strong>Recipient Astrologer:</strong><br>
                    ' . htmlspecialchars($user->name) . '<br>
                    ' . htmlspecialchars($user->email) . '
                </td>
            </tr>
        </table>

        <table class="items-table">
            <tr class="heading-row">
                <td>Transaction ID & Details</td>
                <td class="text-right">Amount</td>
            </tr>';

            foreach ($transactions as $tx) {
                $html .= '<tr class="item-row">
                    <td>
                        TX-' . $tx->id . ' (' . $tx->created_at->toDateString() . ')<br>
                        <span style="font-size: 11px; color: #666;">' . htmlspecialchars($tx->description) . '</span>
                    </td>
                    <td class="text-right">₹' . number_format($tx->amount, 2) . '</td>
                </tr>';
            }

            $html .= '<tr class="total-row">
                <td colspan="2" style="color: #d84315;">Gross Earnings: ₹' . number_format($totalEarnings, 2) . '</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="color: #d84315; border-top: 1px solid #ddd; padding-top: 5px;">Net Payable: ₹' . number_format($totalEarnings, 2) . '</td>
            </tr>
        </table>

        <div class="footer">
            Thank you for your valuable services and contributions on Suryapath Kundli!
        </div>
    </div>
</body>
</html>';

            $fileName = "invoice_{$year}_{$month}.pdf";

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download($fileName);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download invoice: ' . $e->getMessage()
            ], 500);
        }
    }
}
