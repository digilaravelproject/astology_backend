<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * WalletTaxService
 *
 * Tax Calculation & Invoicing Engine:
 * - Dynamic GST tax calculation for consumer wallet recharges
 * - Reverse-calculation of base payout and tax deduction for astrologer payouts
 * - Standardized sequential tax invoice & receipt numbering
 * - Branded DomPDF Tax Invoice / Payout Advice generation and streaming
 */
class WalletTaxService
{
    // =========================================================================
    // 1. CONFIGURATION & SETTINGS
    // =========================================================================

    /**
     * Retrieve all current platform GST and billing configurations.
     */
    public function getGstSettings(): array
    {
        return [
            // Master & Feature Toggles
            'gst_enabled'                  => (bool) Setting::get('gst_enabled', true),
            'gst_recharge_enabled'         => (bool) Setting::get('gst_recharge_enabled', true),
            'gst_withdrawal_enabled'       => (bool) Setting::get('gst_withdrawal_enabled', true),

            // Tax Rates (%)
            'gst_recharge_rate'            => (float) Setting::get('gst_recharge_rate', 18.00),
            'gst_withdrawal_rate'          => (float) Setting::get('gst_withdrawal_rate', 18.00),

            // Thresholds & Financial Limits
            'min_wallet_recharge'          => (float) Setting::get('min_wallet_recharge', 100.00),
            'max_wallet_balance'           => (float) Setting::get('max_wallet_balance', 10000.00),
            'min_withdrawal_amount'        => (float) Setting::get('min_withdrawal_amount', 500.00),
            'min_withdrawal_gst_threshold' => (float) Setting::get('min_withdrawal_gst_threshold', 0.00),

            // Company Legal & Tax Profile Details
            'company_name'                 => (string) Setting::get('company_name', 'Astology Premium Services Pvt Ltd'),
            'company_gstin'                => (string) Setting::get('company_gstin', '07AAAAA0000A1Z5'),
            'company_pan'                  => (string) Setting::get('company_pan', 'AAAAA0000A'),
            'company_address'              => (string) Setting::get('company_address', 'Connaught Place, New Delhi, Delhi 110001'),
            'company_state'                => (string) Setting::get('company_state', 'Delhi'),
            'company_state_code'           => (string) Setting::get('company_state_code', '07'),
            'company_email'                => (string) Setting::get('support_email', 'ops@astologyapp.com'),
        ];
    }

    // =========================================================================
    // 2. TAX CALCULATIONS (RECHARGE & WITHDRAWAL)
    // =========================================================================

    /**
     * Calculate tax breakdown for consumer wallet recharge.
     *
     * Example:
     * User recharges base amount ₹100 with 18% GST:
     * - Base Amount: ₹100.00
     * - GST Amount: ₹18.00
     * - Total Payable via Payment Gateway: ₹118.00
     * - Amount Credited strictly to Wallet Ledger: ₹100.00
     */
    public function calculateRechargeTax(float $baseAmount): array
    {
        $settings = $this->getGstSettings();
        $isGstEnabled = $settings['gst_enabled'] && $settings['gst_recharge_enabled'];

        $base = round(max(0, $baseAmount), 2);

        if ($isGstEnabled) {
            $gstPercent = round($settings['gst_recharge_rate'], 2);
            $gstAmount = round($base * ($gstPercent / 100), 2);
            $totalPayable = round($base + $gstAmount, 2);
        } else {
            $gstPercent = 0.00;
            $gstAmount = 0.00;
            $totalPayable = $base;
        }

        return [
            'base_amount'      => $base,
            'gst_enabled'      => $isGstEnabled,
            'gst_percent'      => $gstPercent,
            'gst_amount'       => $gstAmount,
            'total_payable'    => $totalPayable,
            'credit_to_wallet' => $base,
        ];
    }

    /**
     * Calculate tax breakdown for astrologer withdrawal payout.
     *
     * Example:
     * Astrologer requests gross withdrawal of ₹118 from wallet with 18% GST:
     * - Total Debited from Wallet: ₹118.00
     * - Net Payout Created in Pending Status: ₹100.00
     * - GST Tax Deducted: ₹18.00
     */
    public function calculateWithdrawalTax(float $requestedAmount): array
    {
        $settings = $this->getGstSettings();
        $totalDebited = round(max(0, $requestedAmount), 2);

        $isGstActive = $settings['gst_enabled']
            && $settings['gst_withdrawal_enabled']
            && ($totalDebited >= $settings['min_withdrawal_gst_threshold']);

        if ($isGstActive) {
            $gstPercent = round($settings['gst_withdrawal_rate'], 2);
            // Reverse-calculate base payout from gross debit: gross / (1 + r)
            $basePayout = round($totalDebited / (1 + ($gstPercent / 100)), 2);
            $gstAmount = round($totalDebited - $basePayout, 2);
        } else {
            $gstPercent = 0.00;
            $gstAmount = 0.00;
            $basePayout = $totalDebited;
        }

        return [
            'gross_amount'  => $totalDebited,
            'total_debited' => $totalDebited,
            'base_amount'   => $basePayout,
            'net_payout'    => $basePayout,
            'gst_enabled'   => $isGstActive,
            'gst_percent'   => $gstPercent,
            'gst_amount'    => $gstAmount,
            'payout_amount' => $basePayout,
        ];
    }

    // =========================================================================
    // 3. INVOICE NUMBER GENERATION
    // =========================================================================

    /**
     * Generate standard sequential invoice number.
     *
     * @param string $type e.g. 'REC' (Recharge) or 'WD' (Withdrawal)
     * @param int $transactionId
     * @return string e.g. 'INV-REC-20260821-000042'
     */
    public function generateInvoiceNumber(string $type, int $transactionId): string
    {
        $prefix = strtoupper($type);
        $dateStr = now()->format('Ymd');
        $seq = str_pad((string) $transactionId, 6, '0', STR_PAD_LEFT);

        return "INV-{$prefix}-{$dateStr}-{$seq}";
    }

    // =========================================================================
    // 4. PDF & HTML INVOICE GENERATION
    // =========================================================================

    /**
     * Generate responsive, print-ready HTML for Tax Invoice / Payout Receipt.
     */
    public function generateTaxInvoiceHtml(WalletTransaction $transaction): string
    {
        $settings = $this->getGstSettings();
        $user = $transaction->wallet->user;
        $astrologer = $user?->astrologer;

        $invoiceNo = $transaction->invoice_number ?: $this->generateInvoiceNumber(
            $transaction->transaction_type === 'credit' ? 'REC' : 'WD',
            $transaction->id
        );

        $dateFormatted = Carbon::parse($transaction->created_at)->format('d M Y, h:i A');
        $baseAmount = (float) ($transaction->base_amount ?? $transaction->amount);
        $gstPercent = (float) ($transaction->gst_percent ?? 0.00);
        $gstAmount = (float) ($transaction->gst_amount ?? 0.00);
        $totalAmount = (float) ($transaction->total_amount ?? ($baseAmount + $gstAmount));

        $cgstAmount = round($gstAmount / 2, 2);
        $sgstAmount = round($gstAmount - $cgstAmount, 2);
        $halfPercent = round($gstPercent / 2, 2);

        $isPayout = (is_array($transaction->meta) && (isset($transaction->meta['payout_number']) || isset($transaction->meta['tds_amount'])));
        $isCredit = $transaction->transaction_type === 'credit';
        $title = $isPayout ? 'PAYOUT & TDS DEDUCTION ADVICE' : ($isCredit ? 'TAX INVOICE / RECHARGE RECEIPT' : 'FINANCIAL TRANSACTION RECEIPT');
        $itemDescription = $isPayout 
            ? 'Monthly Astrologer Consultation Settlement (Professional Services)'
            : ($isCredit ? 'Wallet Balance Recharge (Astrology Consultation Credits)' : 'Wallet Debit / Service Charge');

        // Payout specific fields
        $grossAmount = (float) ($transaction->meta['gross_amount'] ?? $transaction->total_amount ?? $transaction->amount);
        $tdsPercent = (float) ($transaction->meta['tds_percent'] ?? 0.00);
        $tdsAmount = (float) ($transaction->meta['tds_amount'] ?? 0.00);
        $netPaidAmount = (float) ($transaction->meta['net_paid_amount'] ?? ($grossAmount - $tdsAmount));
        $paymentMode = $transaction->meta['payment_mode'] ?? ($transaction->payment_provider ? strtoupper($transaction->payment_provider) : 'Digital Gateway');
        $utrNumber = $transaction->meta['utr_number'] ?? null;
        
        $bankDetailsRaw = $transaction->meta['bank_details'] ?? null;
        $bankDetails = is_string($bankDetailsRaw) ? json_decode($bankDetailsRaw, true) : (is_array($bankDetailsRaw) ? $bankDetailsRaw : []);

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . htmlspecialchars($invoiceNo) . '</title>
    <style>' . $this->getInvoiceCssStyles() . '</style>
</head>
<body>
    <div class="invoice-card">
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo-title">' . htmlspecialchars($settings['company_name']) . '</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">' . htmlspecialchars($settings['company_address']) . '</div>
                    <div style="font-size: 11px; color: #64748b;">GSTIN: <strong>' . htmlspecialchars($settings['company_gstin']) . '</strong> | PAN: <strong>' . htmlspecialchars($settings['company_pan']) . '</strong></div>
                </td>
                <td class="text-right">
                    <div class="invoice-badge">' . htmlspecialchars($title) . '</div>
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a;">#' . htmlspecialchars($invoiceNo) . '</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">Date: ' . htmlspecialchars($dateFormatted) . '</div>
                    <div style="font-size: 11px; color: #64748b;">Status: <strong style="color: #16a34a;">' . strtoupper($transaction->status) . '</strong></div>
                </td>
            </tr>
        </table>

        <table class="info-grid">
            <tr>
                <td class="info-box">
                    <div class="info-label">' . ($isPayout ? 'Payee (Astrologer Partner)' : 'Customer / Recipient Details') . '</div>
                    <div class="info-content">
                        <strong>' . htmlspecialchars($user->name ?? 'User') . '</strong><br>
                        ' . ($user->email ? htmlspecialchars($user->email) . '<br>' : '') . '
                        ' . ($user->phone ? 'Phone: +91 ' . htmlspecialchars($user->phone) . '<br>' : '') . '
                        ' . ($astrologer && $astrologer->gst_number ? 'GSTIN: <strong>' . htmlspecialchars($astrologer->gst_number) . '</strong><br>' : '') . '
                        ' . ($astrologer && $astrologer->id_proof_number ? 'PAN / ID: <strong>' . htmlspecialchars($astrologer->id_proof_number) . '</strong><br>' : '') . '
                        ' . (!empty($bankDetails['bank_name']) ? 'Bank: <strong>' . htmlspecialchars($bankDetails['bank_name']) . '</strong> (A/C: ' . htmlspecialchars($bankDetails['account_number'] ?? 'N/A') . ', IFSC: ' . htmlspecialchars($bankDetails['ifsc_code'] ?? 'N/A') . ')<br>' : '') . '
                        User Type: ' . ucfirst(htmlspecialchars($user->user_type ?? 'User')) . '
                    </div>
                </td>
                <td class="info-box text-right">
                    <div class="info-label">Transaction Information</div>
                    <div class="info-content">
                        Transaction ID: <strong>TX-' . $transaction->id . '</strong><br>
                        Payment Mode: <strong>' . htmlspecialchars($paymentMode) . '</strong><br>
                        ' . ($utrNumber ? 'UTR / Ref No: <strong style="font-family: monospace;">' . htmlspecialchars($utrNumber) . '</strong><br>' : '') . '
                        ' . ($transaction->provider_payment_id ? 'Gateway Payment Ref: ' . htmlspecialchars($transaction->provider_payment_id) . '<br>' : '') . '
                        SAC / HSN Code: <strong>998399</strong>
                    </div>
                </td>
            </tr>
        </table>';

        if ($isPayout) {
            $html .= '
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Settlement Description</th>
                    <th class="text-center" style="width: 15%;">Payment Mode</th>
                    <th class="text-center" style="width: 15%;">UTR / Ref</th>
                    <th class="text-right" style="width: 20%;">Gross Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($itemDescription) . '</strong><br>
                        <span style="font-size: 11px; color: #64748b;">Ref: ' . htmlspecialchars($transaction->description ?? 'Payout Settlement') . '</span>
                    </td>
                    <td class="text-center font-bold">' . htmlspecialchars($paymentMode) . '</td>
                    <td class="text-center font-bold" style="font-family: monospace;">' . htmlspecialchars($utrNumber ?? 'N/A') . '</td>
                    <td class="text-right font-bold">Rs. ' . number_format($grossAmount, 2) . '</td>
                </tr>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="font-bold">Gross Settlement Value:</td>
                <td class="text-right font-bold">Rs. ' . number_format($grossAmount, 2) . '</td>
            </tr>';

            if ($tdsAmount > 0) {
                $html .= '
            <tr>
                <td style="color: #dc2626; font-weight: bold;">Less: TDS Deducted (' . number_format($tdsPercent, 2) . '%):</td>
                <td class="text-right font-bold" style="color: #dc2626;">- Rs. ' . number_format($tdsAmount, 2) . '</td>
            </tr>';
            } else {
                $html .= '
            <tr>
                <td>TDS Deducted (0% / Exempt):</td>
                <td class="text-right">Rs. 0.00</td>
            </tr>';
            }

            $html .= '
            <tr class="total-row">
                <td>Net Disbursed to Astrologer:</td>
                <td class="text-right">Rs. ' . number_format($netPaidAmount, 2) . '</td>
            </tr>
        </table>

        <div class="payment-meta">
            <strong>Tax & Compliance Note:</strong> Tax Deducted at Source (TDS) has been deducted in accordance with Section 194J / 194C of the Indian Income Tax Act, 1961. The deducted TDS will be remitted to the Income Tax Department and reflected in your Form 26AS.
        </div>';
        } else {
            $html .= '
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-center" style="width: 15%;">SAC</th>
                    <th class="text-center" style="width: 15%;">Tax Rate</th>
                    <th class="text-right" style="width: 20%;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>' . htmlspecialchars($itemDescription) . '</strong><br>
                        <span style="font-size: 11px; color: #64748b;">Ref: ' . htmlspecialchars($transaction->description ?? 'Wallet Transaction') . '</span>
                    </td>
                    <td class="text-center">998399</td>
                    <td class="text-center">' . number_format($gstPercent, 2) . '%</td>
                    <td class="text-right font-bold">Rs. ' . number_format($baseAmount, 2) . '</td>
                </tr>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="font-bold">Base / Taxable Value:</td>
                <td class="text-right">Rs. ' . number_format($baseAmount, 2) . '</td>
            </tr>';

            if ($gstAmount > 0) {
                $html .= '
            <tr>
                <td>CGST (' . $halfPercent . '%):</td>
                <td class="text-right">Rs. ' . number_format($cgstAmount, 2) . '</td>
            </tr>
            <tr>
                <td>SGST (' . $halfPercent . '%):</td>
                <td class="text-right">Rs. ' . number_format($sgstAmount, 2) . '</td>
            </tr>';
            }

            $html .= '
            <tr class="total-row">
                <td>Total Amount:</td>
                <td class="text-right">Rs. ' . number_format($totalAmount, 2) . '</td>
            </tr>
        </table>

        <div class="payment-meta">
            <strong>Notes & Tax Summary:</strong> This is a computer-generated tax invoice and requires no physical signature. In accordance with Section 31 of the CGST Act 2017, taxes are charged as applicable.
        </div>';
        }

        $html .= '
        <div class="footer">
            Thank you for choosing ' . htmlspecialchars($settings['company_name']) . '! For billing inquiries, contact ' . htmlspecialchars($settings['company_email']) . '.
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Stream or download Tax Invoice PDF file.
     */
    public function downloadInvoicePdf(WalletTransaction $transaction)
    {
        $html = $this->generateTaxInvoiceHtml($transaction);
        $invoiceNo = $transaction->invoice_number ?: 'invoice_' . $transaction->id;
        $fileName = strtolower(str_replace(['/', '\\', ' '], '_', $invoiceNo)) . '.pdf';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }

    // =========================================================================
    // 5. PRIVATE STYLE HELPER
    // =========================================================================

    /**
     * Get CSS styles embedded in the PDF invoice.
     */
    private function getInvoiceCssStyles(): string
    {
        return '
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; margin: 20px; font-size: 13px; line-height: 1.5; }
            .invoice-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; background: #ffffff; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
            .logo-title { font-size: 24px; font-weight: 800; color: #ea580c; text-transform: uppercase; letter-spacing: -0.5px; }
            .invoice-badge { display: inline-block; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
            .info-box { width: 50%; vertical-align: top; }
            .info-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
            .info-content { font-size: 12px; color: #0f172a; line-height: 1.6; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
            .items-table th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 11px; text-transform: uppercase; padding: 10px 12px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
            .items-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
            .summary-table { width: 45%; margin-left: auto; border-collapse: collapse; margin-bottom: 30px; }
            .summary-table td { padding: 6px 12px; font-size: 12px; }
            .total-row { border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; font-weight: 800; font-size: 14px; color: #ea580c; }
            .footer { text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 20px; }
            .payment-meta { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 11px; color: #475569; margin-bottom: 20px; }
        ';
    }
}
