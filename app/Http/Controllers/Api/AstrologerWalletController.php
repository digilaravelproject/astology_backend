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
    /**
     * Get paginated monthly payout settlements for the astrologer.
     */
    public function payouts(Request $request): JsonResponse
    {
        try {
            $payouts = $this->walletService->getPayoutsHistory($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $payouts
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch payout history.'
            ], 500);
        }
    }

    /**
     * Download official TDS Payout Settlement Advice PDF for an astrologer payout.
     */
    public function payoutReceipt(Request $request, int $id)
    {
        try {
            $astrologer = $request->user()->astrologer;
            if (!$astrologer) {
                return response()->json(['status' => 'error', 'message' => 'Astrologer profile not found.'], 404);
            }

            $payout = \App\Models\AstrologerPayout::where('astrologer_id', $astrologer->id)->findOrFail($id);
            /** @var \App\Services\AstrologerPayoutService $payoutService */
            $payoutService = app(\App\Services\AstrologerPayoutService::class);

            return $payoutService->generateSettlementSlipPdf($payout);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to generate payout advice slip.'], 500);
        }
    }

    /**
     * Get withdrawal history for the astrologer (Alias for payouts).
     */
    public function withdrawals(Request $request): JsonResponse
    {
        return $this->payouts($request);
    }

    /**
     * Get withdrawal configuration and monthly settlement schedule for the astrologer.
     */
    public function withdrawalConfig(Request $request): JsonResponse
    {
        try {
            $config = $this->walletService->getWithdrawalConfig($request->user());
            return response()->json([
                'status' => 'success',
                'data' => $config,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch withdrawal configuration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Self-withdrawal is disabled. Payouts are processed by Admin monthly under TDS guidelines.
     */
    public function withdraw(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'code'    => 'SELF_WITHDRAWAL_DISABLED',
            'message' => 'Manual self-withdrawal is disabled. Partner earnings are settled automatically every monthly billing cycle directly to your verified bank account after TDS deduction.'
        ], 403);
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

            $astrologer = $user->astrologer;

            // Fetch transactions for that year-month
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $transactions = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transaction_type', 'credit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $payouts = $astrologer ? \App\Models\AstrologerPayout::where('astrologer_id', $astrologer->id)
                ->where('status', 'completed')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->get() : collect();

            $totalEarnings = (float) $transactions->sum('amount');
            $totalTdsDeducted = (float) $payouts->sum('tds_amount');
            $totalNetPaid = (float) $payouts->sum('net_paid_amount');
            $netPayable = max(0, $totalEarnings - $totalTdsDeducted);

            $monthName = $startDate->format('F Y');

            // Build dynamic styled HTML template
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - ' . $monthName . '</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; margin: 15px; font-size: 13px; line-height: 1.5; }
        .invoice-box { padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        .header-table, .info-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-title { font-size: 24px; line-height: 30px; color: #ff5722; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .heading-row { background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #475569; }
        .heading-row td { padding: 10px 12px; font-size: 11px; text-transform: uppercase; }
        .item-row td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        .total-row td { padding: 8px 12px; text-align: right; font-weight: bold; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #eee; padding-top: 15px; }
        .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 11px; color: #475569; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td class="header-title">SURYAPATH KUNDLI</td>
                <td class="text-right">
                    <strong>Invoice #:</strong> INV-' . $year . '-' . $month . '<br>
                    <strong>Date:</strong> ' . now()->format('d M Y') . '<br>
                    <strong>Billing Period:</strong> ' . $monthName . '
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Platform Operator:</strong><br>
                    Suryapath Kundli Services<br>
                    support@suryapathkundli.com<br>
                    https://suryapathkundli.com
                </td>
                <td class="text-right" style="width: 50%; vertical-align: top;">
                    <strong>Partner Astrologer:</strong><br>
                    ' . htmlspecialchars($user->name) . '<br>
                    ' . ($user->phone ? 'Phone: +91 ' . htmlspecialchars($user->phone) . '<br>' : '') . '
                    ' . ($astrologer && $astrologer->id_proof_number ? 'PAN / ID: <strong>' . htmlspecialchars($astrologer->id_proof_number) . '</strong><br>' : '') . '
                    ' . htmlspecialchars($user->email ?? '') . '
                </td>
            </tr>
        </table>

        <table class="items-table">
            <tr class="heading-row">
                <td>Service / Consultation Details</td>
                <td class="text-center" style="width: 25%;">Date</td>
                <td class="text-right" style="width: 25%;">Astrologer Share (Rs.)</td>
            </tr>';

            if ($transactions->isEmpty()) {
                $html .= '<tr class="item-row">
                    <td colspan="3" class="text-center" style="color: #94a3b8; padding: 20px;">No billable consultation sessions recorded for this month.</td>
                </tr>';
            } else {
                foreach ($transactions as $tx) {
                    $html .= '<tr class="item-row">
                        <td>
                            <strong>TX-' . $tx->id . '</strong><br>
                            <span style="font-size: 11px; color: #64748b;">' . htmlspecialchars($tx->description ?? 'Astrology Consultation') . '</span>
                        </td>
                        <td class="text-center">' . $tx->created_at->format('d M Y') . '</td>
                        <td class="text-right font-bold">Rs. ' . number_format($tx->amount, 2) . '</td>
                    </tr>';
                }
            }

            $html .= '<tr class="total-row" style="border-top: 2px solid #e2e8f0;">
                <td colspan="3" style="color: #1e293b;">Gross Earnings: Rs. ' . number_format($totalEarnings, 2) . '</td>
            </tr>';

            if ($totalTdsDeducted > 0) {
                $html .= '<tr class="total-row">
                    <td colspan="3" style="color: #dc2626;">Less: TDS Deducted (Tax at Source): - Rs. ' . number_format($totalTdsDeducted, 2) . '</td>
                </tr>';
            }

            $html .= '<tr class="total-row" style="border-top: 1px solid #ddd; font-size: 15px;">
                <td colspan="3" style="color: #16a34a;">Net Disbursed / Payable: Rs. ' . number_format($netPayable, 2) . '</td>
            </tr>
        </table>';

            if ($payouts->isNotEmpty()) {
                $html .= '
        <div style="margin-top: 25px;">
            <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; color: #475569; margin-bottom: 8px;">Disbursement & Settlement History:</div>
            <table class="items-table">
                <tr class="heading-row">
                    <td>Payout Reference</td>
                    <td class="text-center">Mode & UTR</td>
                    <td class="text-center">Settled Date</td>
                    <td class="text-right">Net Transferred (Rs.)</td>
                </tr>';
                foreach ($payouts as $p) {
                    $html .= '<tr class="item-row">
                        <td><strong>' . htmlspecialchars($p->payout_number) . '</strong></td>
                        <td class="text-center">' . htmlspecialchars($p->payment_mode) . ($p->utr_number ? ' (' . htmlspecialchars($p->utr_number) . ')' : '') . '</td>
                        <td class="text-center">' . $p->payment_date->format('d M Y') . '</td>
                        <td class="text-right font-bold" style="color: #16a34a;">Rs. ' . number_format($p->net_paid_amount, 2) . '</td>
                    </tr>';
                }
                $html .= '</table>
        </div>';
            }

            $html .= '
        <div class="meta-box">
            <strong>Compliance & Tax Notice:</strong> This statement reflects monthly professional consultation earnings and applicable TDS under the Indian Income Tax Act. Retain this invoice for income tax filing and 26AS reconciliation.
        </div>

        <div class="footer">
            Thank you for your valuable spiritual consultations on Suryapath Kundli! For financial queries, contact support@suryapathkundli.com.
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

    /**
     * Download itemized withdrawal receipt / tax advice PDF for a specific transaction.
     */
    public function downloadWithdrawalReceipt(Request $request, $id)
    {
        try {
            $user = $request->user();
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                return response()->json(['status' => 'error', 'message' => 'Wallet not found.'], 404);
            }

            $transaction = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                ->where('id', $id)
                ->first();

            if (!$transaction) {
                return response()->json(['status' => 'error', 'message' => 'Withdrawal transaction not found.'], 404);
            }

            $taxService = app(\App\Services\WalletTaxService::class);
            return $taxService->downloadInvoicePdf($transaction);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download receipt: ' . $e->getMessage()
            ], 500);
        }
    }
}
