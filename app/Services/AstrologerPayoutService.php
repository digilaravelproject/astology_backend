<?php

namespace App\Services;

use App\Models\Astrologer;
use App\Models\AstrologerBankAccount;
use App\Models\AstrologerPayout;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class AstrologerPayoutService
{
    protected TDSCalculatorService $tdsCalculator;

    public function __construct(TDSCalculatorService $tdsCalculator)
    {
        $this->tdsCalculator = $tdsCalculator;
    }

    /**
     * Get financial and bank metrics for processing an astrologer payout.
     */
    public function getAstrologerPayoutContext(int $astrologerId): array
    {
        $astrologer = Astrologer::with(['user', 'bankAccounts'])->findOrFail($astrologerId);
        $user = $astrologer->user;
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0.00]);

        $verifiedBankAccounts = $astrologer->bankAccounts()->where('is_active', true)->get();

        $totalPaidOut = (float) AstrologerPayout::where('astrologer_id', $astrologer->id)
            ->where('status', 'completed')
            ->sum('net_paid_amount');

        $totalTdsDeducted = (float) AstrologerPayout::where('astrologer_id', $astrologer->id)
            ->where('status', 'completed')
            ->sum('tds_amount');

        $lifetimeEarnings = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'astrologer'            => $astrologer,
            'user'                  => $user,
            'current_balance'       => (float) $wallet->balance,
            'total_paid_out'        => $totalPaidOut,
            'total_tds_deducted'    => $totalTdsDeducted,
            'lifetime_earnings'     => $lifetimeEarnings,
            'bank_accounts'         => $verifiedBankAccounts,
            'tds_config'            => $this->tdsCalculator->getTdsSettings(),
        ];
    }

    /**
     * Process an admin manual payout with atomic wallet debit and TDS calculation.
     */
    public function processManualPayout(int $astrologerId, float $grossAmount, array $data, int $adminId): AstrologerPayout
    {
        $astrologer = Astrologer::with('user')->findOrFail($astrologerId);
        $user = $astrologer->user;

        if (!$user) {
            throw new Exception("Linked user account not found for Astrologer #{$astrologerId}.", 404);
        }

        $gross = round(max(0, $grossAmount), 2);
        if ($gross <= 0) {
            throw new Exception("Gross settlement amount must be greater than zero.", 422);
        }

        // Bank Account Resolution
        $bankAccountId = $data['bank_account_id'] ?? null;
        $bankAccount = null;
        if ($bankAccountId) {
            $bankAccount = AstrologerBankAccount::where('astrologer_id', $astrologerId)
                ->where('id', $bankAccountId)
                ->first();
        }

        if (!$bankAccount) {
            $bankAccount = AstrologerBankAccount::where('astrologer_id', $astrologerId)
                ->where('is_default', true)
                ->first() ?? AstrologerBankAccount::where('astrologer_id', $astrologerId)->first();
        }

        $bankDetailsSnapshot = $bankAccount ? [
            'account_holder_name' => $bankAccount->account_holder_name,
            'bank_name'           => $bankAccount->bank_name,
            'account_number'      => $bankAccount->account_number,
            'ifsc_code'           => $bankAccount->ifsc_code,
        ] : [
            'account_holder_name' => $user->name,
            'bank_name'           => $data['custom_bank_name'] ?? 'Direct Transfer',
            'account_number'      => $data['custom_account_number'] ?? 'N/A',
            'ifsc_code'           => $data['custom_ifsc'] ?? 'N/A',
        ];

        // TDS & Net Disbursement Calculation
        $taxBreakdown = $this->tdsCalculator->calculatePayoutTDS($gross);
        $payoutNumber = 'PAY-' . date('Y') . '-' . strtoupper(Str::random(6));

        // Atomic Transaction with Pessimistic Locking
        return DB::transaction(function () use (
            $astrologer,
            $user,
            $gross,
            $taxBreakdown,
            $data,
            $bankAccount,
            $bankDetailsSnapshot,
            $payoutNumber,
            $adminId
        ) {
            // Lock wallet row for update
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0.00]);
            }

            if ($gross > (float) $wallet->balance) {
                $formattedBal = number_format($wallet->balance, 2);
                $formattedReq = number_format($gross, 2);
                throw new Exception("Insufficient wallet balance. Available balance is ₹{$formattedBal}, but requested gross payout is ₹{$formattedReq}.", 422);
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = round($balanceBefore - $gross, 2);

            // 1. Debit wallet balance
            $wallet->balance = $balanceAfter;
            $wallet->save();

            // 2. Create ledger transaction entry
            $transaction = WalletTransaction::create([
                'wallet_id'         => $wallet->id,
                'transaction_type'  => 'debit',
                'amount'            => $gross,
                'base_amount'       => $taxBreakdown['net_paid_amount'],
                'gst_percent'       => 0.00,
                'gst_amount'        => 0.00,
                'total_amount'      => $gross,
                'status'            => 'completed',
                'description'       => "Monthly Payout Settlement ({$payoutNumber})",
                'meta'              => [
                    'payout_number'       => $payoutNumber,
                    'gross_amount'        => $gross,
                    'tds_percent'         => $taxBreakdown['tds_percent'],
                    'tds_amount'          => $taxBreakdown['tds_amount'],
                    'net_paid_amount'     => $taxBreakdown['net_paid_amount'],
                    'payment_mode'        => $data['payment_mode'] ?? 'Bank Transfer',
                    'utr_number'          => $data['utr_number'] ?? null,
                    'bank_details'        => $bankDetailsSnapshot,
                    'payment_date'        => $data['payment_date'] ?? now()->toDateString(),
                    'processed_by_admin'  => $adminId,
                ],
                'balance_before'    => $balanceBefore,
                'balance_after'     => $balanceAfter,
                'invoice_number'    => $payoutNumber,
            ]);

            // 3. Create AstrologerPayout record
            $payout = AstrologerPayout::create([
                'payout_number'         => $payoutNumber,
                'astrologer_id'         => $astrologer->id,
                'user_id'               => $user->id,
                'wallet_transaction_id' => $transaction->id,
                'gross_amount'          => $gross,
                'tds_percent'           => $taxBreakdown['tds_percent'],
                'tds_amount'            => $taxBreakdown['tds_amount'],
                'net_paid_amount'       => $taxBreakdown['net_paid_amount'],
                'payment_mode'          => $data['payment_mode'] ?? 'Bank Transfer',
                'utr_number'            => $data['utr_number'] ?? null,
                'bank_account_id'       => $bankAccount?->id,
                'bank_details_snapshot' => $bankDetailsSnapshot,
                'payment_date'          => $data['payment_date'] ?? now()->toDateString(),
                'notes'                 => $data['notes'] ?? null,
                'receipt_proof'         => $data['receipt_proof'] ?? null,
                'status'                => 'completed',
                'processed_by'          => $adminId,
            ]);

            // 4. Dispatch Push & In-App Notification
            try {
                $netFormatted = number_format($taxBreakdown['net_paid_amount'], 2);
                $utrText = $payout->utr_number ? " (UTR: {$payout->utr_number})" : "";
                NotificationHelper::send(
                    userId: $user->id,
                    title: 'Payout Processed 💰',
                    body: "Your monthly settlement of ₹{$netFormatted} has been disbursed via {$payout->payment_mode}{$utrText}.",
                    meta: [
                        'type'          => 'payout_settlement',
                        'payout_id'     => (string) $payout->id,
                        'payout_number' => $payout->payout_number,
                        'net_amount'    => (string) $payout->net_paid_amount,
                        'screen_route'  => '/wallet/payouts',
                    ]
                );
            } catch (\Throwable $ne) {
                Log::error('Payout notification failed: ' . $ne->getMessage());
            }

            return $payout;
        });
    }

    /**
     * Generate branded Settlement Advice / Voucher PDF.
     */
    public function generateSettlementSlipPdf(AstrologerPayout $payout): Response
    {
        $payout->load(['astrologer.user', 'processedByAdmin']);
        $astrologer = $payout->astrologer;
        $user = $payout->user;

        $company = [
            'name'       => (string) Setting::get('company_name', 'Astology Premium Services Pvt Ltd'),
            'gstin'      => (string) Setting::get('company_gstin', '07AAAAA0000A1Z5'),
            'pan'        => (string) Setting::get('company_pan', 'AAAAA0000A'),
            'address'    => (string) Setting::get('company_address', 'Connaught Place, New Delhi, Delhi 110001'),
            'state'      => (string) Setting::get('company_state', 'Delhi'),
            'state_code' => (string) Setting::get('company_state_code', '07'),
            'email'      => (string) Setting::get('support_email', 'finance@astologyapp.com'),
        ];

        $html = view('invoices.payout_settlement_slip', compact('payout', 'astrologer', 'user', 'company'))->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        $filename = "Settlement-Advice-{$payout->payout_number}.pdf";

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
