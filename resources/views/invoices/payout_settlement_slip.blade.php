<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payout Settlement Slip - {{ $payout->payout_number }}</title>
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; margin: 20px; font-size: 13px; line-height: 1.5; }
        .invoice-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; background: #ffffff; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        .logo-title { font-size: 24px; font-weight: 800; color: #ea580c; text-transform: uppercase; letter-spacing: -0.5px; }
        .invoice-badge { display: inline-block; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .info-box { width: 50%; vertical-align: top; }
        .info-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-content { font-size: 12px; color: #0f172a; line-height: 1.6; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items-table th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 11px; text-transform: uppercase; padding: 10px 12px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        .summary-table { width: 50%; margin-left: auto; border-collapse: collapse; margin-bottom: 30px; }
        .summary-table td { padding: 6px 12px; font-size: 12px; }
        .total-row { border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; font-weight: 800; font-size: 14px; color: #16a34a; }
        .footer { text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 20px; }
        .payment-meta { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 11px; color: #475569; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="invoice-card">
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo-title">{{ $company['name'] }}</div>
                    <span style="font-size: 11px; color: #64748b;">Astrologer Partner Settlement & TDS Advice</span>
                </td>
                <td class="text-right">
                    <div class="invoice-badge">Payout Advice</div>
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">#{{ $payout->payout_number }}</div>
                    <div style="font-size: 11px; color: #64748b;">Date: {{ $payout->payment_date->format('d M Y') }}</div>
                    <div style="font-size: 11px; color: #64748b;">Status: <strong style="color: #16a34a; text-transform: uppercase;">{{ $payout->status }}</strong></div>
                </td>
            </tr>
        </table>

        <table class="info-grid">
            <tr>
                <td class="info-box">
                    <div class="info-label">Payer (Platform Information):</div>
                    <div class="info-content">
                        <strong>{{ $company['name'] }}</strong><br>
                        {{ $company['address'] }}<br>
                        State: {{ $company['state'] }} (Code: {{ $company['state_code'] }})<br>
                        PAN: <strong>{{ $company['pan'] }}</strong> | GSTIN: <strong>{{ $company['gstin'] }}</strong><br>
                        Email: {{ $company['email'] }}
                    </div>
                </td>
                <td class="info-box" style="padding-left: 20px;">
                    <div class="info-label">Payee (Astrologer Partner):</div>
                    <div class="info-content">
                        <strong>{{ $user->name }}</strong> (ID: #{{ $astrologer->id }})<br>
                        Phone: {{ $user->phone ?? 'N/A' }} | Email: {{ $user->email ?? 'N/A' }}<br>
                        PAN / Tax ID: <strong>{{ $astrologer->id_proof_number ?? 'Not Provided' }}</strong><br>
                        Bank: <strong>{{ $payout->bank_details_snapshot['bank_name'] ?? 'Bank' }}</strong><br>
                        A/C No: <strong>{{ $payout->bank_details_snapshot['account_number'] ?? 'N/A' }}</strong> (IFSC: {{ $payout->bank_details_snapshot['ifsc_code'] ?? 'N/A' }})
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Settlement Description</th>
                    <th class="text-center" style="width: 15%;">Payment Mode</th>
                    <th class="text-center" style="width: 15%;">UTR / Ref No</th>
                    <th class="text-right" style="width: 15%;">Gross Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Monthly Astrologer Consultation Settlement</strong><br>
                        <span style="font-size: 11px; color: #64748b;">
                            Consultation Earnings Settled: {{ $payout->notes ?? 'Standard monthly payout cycle' }}
                        </span>
                    </td>
                    <td class="text-center font-bold">{{ $payout->payment_mode }}</td>
                    <td class="text-center font-bold" style="font-family: monospace;">{{ $payout->utr_number ?? 'N/A' }}</td>
                    <td class="text-right font-bold">Rs. {{ number_format($payout->gross_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="font-bold">Gross Settlement Value:</td>
                <td class="text-right font-bold">Rs. {{ number_format($payout->gross_amount, 2) }}</td>
            </tr>
            @if($payout->tds_amount > 0)
            <tr>
                <td style="color: #dc2626;">Less: TDS Deducted ({{ number_format($payout->tds_percent, 2) }}%):</td>
                <td class="text-right font-bold" style="color: #dc2626;">- Rs. {{ number_format($payout->tds_amount, 2) }}</td>
            </tr>
            @else
            <tr>
                <td>TDS Deducted (0% / Exempt):</td>
                <td class="text-right">Rs. 0.00</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Net Disbursed to Astrologer:</td>
                <td class="text-right">Rs. {{ number_format($payout->net_paid_amount, 2) }}</td>
            </tr>
        </table>

        <div class="payment-meta">
            <strong>Tax & Compliance Note:</strong> Tax Deducted at Source (TDS) has been deducted in accordance with applicable provisions of the Indian Income Tax Act, 1961. The deducted TDS will be remitted to the Income Tax Department and reflected in your Form 26AS.
        </div>

        <div class="footer">
            Computer-generated payment voucher issued by {{ $company['name'] }}. For payout support, contact {{ $company['email'] }}.
        </div>
    </div>
</body>
</html>
