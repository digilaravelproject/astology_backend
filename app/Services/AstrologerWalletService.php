<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\AstrologerBankAccount;
use App\Models\Setting;
use App\Helpers\MediaHelper;
use App\Services\NotificationHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AstrologerWalletService
 *
 * Handles astrologer financial operations:
 * - Real-time earnings aggregation and rankings
 * - Filtered transaction and payout history
 * - Dynamic GST tax calculations and withdrawal rule resolution
 * - Deadlock-safe, pessimistic locking withdrawal execution
 * - Monthly financial invoice statements
 */
class AstrologerWalletService
{
    // =========================================================================
    // 1. WALLET SUMMARY & EARNINGS METRICS
    // =========================================================================

    /**
     * Get wallet balance, time-scoped earnings metrics, and leaderboard ranks.
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user->id);

        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        // Single index-backed conditional aggregation query instead of 4 separate queries
        $stats = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $threeMonthsAgo)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END), 0) as today_earning,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END), 0) as weekly_earning,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN amount ELSE 0 END), 0) as monthly_earning,
                COALESCE(SUM(amount), 0) as three_month_earning
            ", [$today, $startOfWeek, $startOfMonth])
            ->first();

        $todayEarning = (float) ($stats->today_earning ?? 0);
        $weeklyEarning = (float) ($stats->weekly_earning ?? 0);
        $monthlyEarning = (float) ($stats->monthly_earning ?? 0);
        $threeMonthEarning = (float) ($stats->three_month_earning ?? 0);

        // Weekly and all-time ranking calculations via Redis-cached dictionaries
        $weeklyRank = $this->calculateWeeklyRank($user->id);
        $allTimeRank = $this->calculateAllTimeRank($user->id);

        $astrologer = $user->astrologer;
        $profilePhotoUrl = MediaHelper::getUrl($astrologer?->profile_photo);

        return [
            'total_balance' => (float) $wallet->balance,
            'today_earning' => round($todayEarning, 2),
            'weekly_earning' => round($weeklyEarning, 2),
            'monthly_earning' => round($monthlyEarning, 2),
            'three_month_earning' => round($threeMonthEarning, 2),
            'rank' => $weeklyRank,
            'all_time_rank' => $allTimeRank,
            'name' => $user->name,
            'profile_photo' => $profilePhotoUrl,
        ];
    }

    // =========================================================================
    // 2. EARNINGS & WITHDRAWAL HISTORY
    // =========================================================================

    /**
     * Get paginated and filtered earnings history for the astrologer.
     */
    public function getEarningsHistory(User $user, ?string $filter = null): LengthAwarePaginator
    {
        $wallet = $this->getOrCreateWallet($user->id);

        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->latest();

        if ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'weekly') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($filter === 'monthly') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        }

        return $query->paginate(15);
    }

    /**
     * Get paginated monthly payout settlements for the astrologer.
     */
    public function getPayoutsHistory(User $user): LengthAwarePaginator
    {
        $astrologer = $user->astrologer;
        if (!$astrologer) {
            return new LengthAwarePaginator([], 0, 15);
        }

        return \App\Models\AstrologerPayout::with('bankAccount')
            ->where('astrologer_id', $astrologer->id)
            ->latest('payment_date')
            ->paginate(15);
    }

    /**
     * Get paginated withdrawal requests and payout history for the astrologer.
     */
    public function getWithdrawalHistory(User $user): LengthAwarePaginator
    {
        return $this->getPayoutsHistory($user);
    }

    // =========================================================================
    // 3. WITHDRAWAL & PAYOUT POLICY CONFIGURATION
    // =========================================================================

    /**
     * Get live balance, TDS tax rules, and monthly settlement schedule for the astrologer.
     */
    public function getWithdrawalConfig(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user->id);

        /** @var \App\Services\TDSCalculatorService $tdsService */
        $tdsService = app(\App\Services\TDSCalculatorService::class);
        $tdsConfig = $tdsService->getTdsSettings();

        $astrologer = $user->astrologer;
        $totalPaidOut = 0.00;
        $totalTdsDeducted = 0.00;
        if ($astrologer) {
            $totalPaidOut = (float) \App\Models\AstrologerPayout::where('astrologer_id', $astrologer->id)
                ->where('status', 'completed')
                ->sum('net_paid_amount');

            $totalTdsDeducted = (float) \App\Models\AstrologerPayout::where('astrologer_id', $astrologer->id)
                ->where('status', 'completed')
                ->sum('tds_amount');
        }

        return [
            'total_balance'          => (float) $wallet->balance,
            'available_balance'      => (float) $wallet->balance,
            'total_paid_out'         => round($totalPaidOut, 2),
            'total_tds_deducted'     => round($totalTdsDeducted, 2),
            'payout_frequency'       => 'Monthly Settlement',
            'payout_processing_type' => 'Admin Direct Disbursement',
            'self_withdrawal_status' => 'disabled',
            'tds_enabled'            => $tdsConfig['tds_enabled'],
            'tds_rate'               => $tdsConfig['tds_rate'],
            'tds_threshold'          => $tdsConfig['tds_threshold'],
            'settlement_notice'      => 'Payouts are disbursed automatically every monthly billing cycle directly to your verified bank account after TDS deduction.',
        ];
    }

    // =========================================================================
    // 4. WITHDRAWAL EXECUTION (PESSIMISTIC LOCKING & DEADLOCK RETRY)
    // =========================================================================

    /**
     * Submit a withdrawal request with row-level pessimistic locking and automated GST calculation.
     *
     * @throws Exception
     */
    public function requestWithdrawal(User $user, float $amount, int $bankAccountId): array
    {
        // 1. Role & Profile Guard
        if ($user->user_type !== 'astrologer' || !$user->astrologer) {
            throw new Exception('Unauthorized. Only registered astrologers can request withdrawals.', 403);
        }

        $astrologer = $user->astrologer;

        // 2. Active Bank Account Verification
        $bankAccount = AstrologerBankAccount::where('astrologer_id', $astrologer->id)
            ->where('id', $bankAccountId)
            ->where('is_active', true)
            ->first();

        if (!$bankAccount) {
            throw new Exception('Invalid or inactive bank account selected.', 422);
        }

        // 3. Amount & Minimum Threshold Validation
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            throw new Exception('Please enter a valid withdrawal amount.', 422);
        }

        $minWithdrawal = (float) Setting::get('min_withdrawal_amount', 0.00);
        if ($minWithdrawal > 0 && $amount < $minWithdrawal) {
            throw new Exception("The minimum withdrawal limit is ₹" . number_format($minWithdrawal, 2) . ". Please enter an amount equal to or above ₹" . number_format($minWithdrawal, 2) . ".", 422);
        }

        /** @var WalletTaxService $taxService */
        $taxService = app(WalletTaxService::class);
        $taxBreakdown = $taxService->calculateWithdrawalTax($amount);

        // 4. Concurrency Guard: DB Transaction with Deadlock Retries (3 attempts)
        return DB::transaction(function () use ($user, $amount, $bankAccount, $taxBreakdown, $taxService) {
            // Lock wallet row for update
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);
            }

            // Pessimistic sum of pending debits to prevent race condition/overdraft
            $pendingWithdrawalSum = (float) WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transaction_type', 'debit')
                ->where('status', 'pending')
                ->lockForUpdate()
                ->sum('amount');

            $availableBalance = round((float) $wallet->balance - $pendingWithdrawalSum, 2);

            if ($amount > $availableBalance) {
                $formattedTotal = number_format($wallet->balance, 2);
                $formattedPending = number_format($pendingWithdrawalSum, 0);
                throw new Exception("Insufficient available balance. Your total balance is ₹{$formattedTotal}, but you have ₹{$formattedPending} in pending withdrawals.", 422);
            }

            // Create pending debit transaction with tax breakdown
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'base_amount' => $taxBreakdown['base_amount'],
                'gst_percent' => $taxBreakdown['gst_percent'],
                'gst_amount' => $taxBreakdown['gst_amount'],
                'total_amount' => $amount,
                'status' => 'pending',
                'description' => 'Withdrawal Request',
                'meta' => [
                    'bank_account_id' => $bankAccount->id,
                    'account_holder_name' => $bankAccount->account_holder_name,
                    'bank_name' => $bankAccount->bank_name,
                    'account_number' => $bankAccount->account_number,
                    'ifsc_code' => $bankAccount->ifsc_code,
                    'tax_breakdown' => $taxBreakdown,
                    'requested_at' => now()->toDateTimeString(),
                ],
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
            ]);

            // Assign sequential tax advice / payout receipt invoice number
            $transaction->invoice_number = $taxService->generateInvoiceNumber('WD', $transaction->id);
            $transaction->save();

            // Dispatch In-App & Push Notification
            try {
                NotificationHelper::send(
                    userId: $user->id,
                    title: 'Withdrawal Requested 🏦',
                    body: "Your payout request for ₹" . number_format($taxBreakdown['base_amount'], 2) . " (Total debited: ₹" . number_format($amount, 2) . ") has been submitted and is under review.",
                    meta: [
                        'type' => 'wallet',
                        'transaction_id' => (string) $transaction->id,
                        'amount' => (string) $amount,
                        'screen_route' => '/wallet',
                    ]
                );
            } catch (\Throwable $ne) {
                Log::error('Withdrawal notification failed: ' . $ne->getMessage());
            }

            return [
                'transaction' => $transaction,
                'tax_breakdown' => $taxBreakdown,
                'available_balance' => round($availableBalance - $amount, 2),
            ];
        }, 3);
    }

    // =========================================================================
    // 5. LEADERBOARD & WEEKLY RANKINGS
    // =========================================================================

    /**
     * Get weekly rankings leaderboard for astrologers based on completed earnings.
     */
    public function getWeeklyRankings(User $user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();

        // Cache the computed rankings list in Redis with -80s to +80s jitter to avoid cache stampede
        $rankings = \App\Helpers\CacheHelper::remember('astrologer:weekly_rankings_raw', 300, function () use ($startOfWeek) {
            return DB::table('astrologers')
                ->join('users', 'users.id', '=', 'astrologers.user_id')
                ->leftJoin('wallets', 'wallets.user_id', '=', 'astrologers.user_id')
                ->leftJoin('wallet_transactions', function ($join) use ($startOfWeek) {
                    $join->on('wallet_transactions.wallet_id', '=', 'wallets.id')
                        ->where('wallet_transactions.transaction_type', '=', 'credit')
                        ->where('wallet_transactions.status', '=', 'completed')
                        ->where('wallet_transactions.created_at', '>=', $startOfWeek);
                })
                ->select(
                    'astrologers.id as astrologer_id',
                    'users.id as user_id',
                    'users.name',
                    'astrologers.profile_photo',
                    DB::raw('COALESCE(SUM(wallet_transactions.amount), 0) as weekly_earnings')
                )
                ->groupBy('astrologers.id', 'users.id', 'users.name', 'astrologers.profile_photo')
                ->orderByDesc('weekly_earnings')
                ->get();
        });

        $myRank = null;
        $myEarnings = 0.00;

        foreach ($rankings as $index => $ranking) {
            $weeklyEarnings = (float) $ranking->weekly_earnings;
            if ($ranking->user_id == $user->id) {
                $myRank = $index + 1;
                $myEarnings = $weeklyEarnings;
            }
        }

        $top10 = $rankings->take(10)->values()->map(function ($item, $index) {
            $photo = MediaHelper::getUrl($item->profile_photo);
            return [
                'rank' => $index + 1,
                'astrologer_id' => $item->astrologer_id,
                'user_id' => $item->user_id,
                'name' => $item->name,
                'profile_photo' => $photo,
                'weekly_earnings' => (float) $item->weekly_earnings,
            ];
        });

        return [
            'top_astrologers' => $top10,
            'my_rank' => $myRank ?? (count($rankings) + 1),
            'my_weekly_earnings' => $myEarnings,
        ];
    }

    // =========================================================================
    // 6. MONTHLY INVOICE STATEMENTS & SETTLEMENTS
    // =========================================================================

    /**
     * Get monthly financial invoice statements and settlement summary for the astrologer.
     */
    public function getInvoicesSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user->id);

        // 1. Total completed earnings
        $totalEarnings = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        // 2. Total completed/approved withdrawals
        $totalWithdrawn = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'debit')
            ->whereIn('status', ['completed', 'approved'])
            ->where(function ($query) {
                $query->where('description', 'like', '%Withdrawal%')
                    ->orWhere('description', 'like', '%payout%');
            })
            ->sum('amount');

        // 3. Group credit transactions by YYYY-MM
        $creditTransactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        $grouped = $creditTransactions->groupBy(function ($item) {
            return Carbon::parse($item->created_at)->format('Y-m');
        });

        $invoices = [];
        foreach ($grouped as $yearMonth => $txs) {
            $parsedDate = Carbon::parse($yearMonth . '-01');
            $monthName = $parsedDate->format('F Y');
            $year = $parsedDate->format('Y');
            $month = $parsedDate->format('m');

            $grossEarnings = (float) $txs->sum('amount');
            $netPayable = $grossEarnings;

            $startDate = $parsedDate->copy()->startOfMonth();
            $endDate = $parsedDate->copy()->endOfMonth();

            $withdrawnForMonth = (float) WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transaction_type', 'debit')
                ->whereIn('status', ['completed', 'approved'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where(function ($query) {
                    $query->where('description', 'like', '%Withdrawal%')
                        ->orWhere('description', 'like', '%payout%');
                })
                ->sum('amount');

            $invoices[] = [
                'month_name' => $monthName,
                'gross_earnings' => round($grossEarnings, 2),
                'net_payable' => round($netPayable, 2),
                'total_withdrawn' => round($withdrawnForMonth, 2),
                'status' => 'Paid',
                'download_url' => url("/api/v1/astrologer/wallet/invoices/{$year}/{$month}/download"),
            ];
        }

        // Current month stats
        $currentYearMonth = Carbon::now()->format('Y-m');
        $currentMonthTxs = $grouped->get($currentYearMonth);
        $currentMonthEarnings = $currentMonthTxs ? (float) $currentMonthTxs->sum('amount') : 0.00;

        $currentMonthWithdrawals = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'debit')
            ->whereIn('status', ['completed', 'approved'])
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->where(function ($query) {
                $query->where('description', 'like', '%Withdrawal%')
                    ->orWhere('description', 'like', '%payout%');
            })
            ->sum('amount');

        return [
            'total_earnings' => round($totalEarnings, 2),
            'total_withdrawn' => round($totalWithdrawn, 2),
            'total_invoices' => count($invoices),
            'status' => count($invoices) > 0 ? 'All Paid' : 'No Invoices',
            'current_month' => [
                'month_name' => Carbon::now()->format('F Y'),
                'gross_earnings' => round($currentMonthEarnings, 2),
                'net_payable' => round($currentMonthEarnings, 2),
                'total_withdrawn' => round($currentMonthWithdrawals, 2),
                'status' => 'Paid',
            ],
            'invoices' => $invoices,
        ];
    }

    // =========================================================================
    // 7. PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Retrieve or create wallet instance for a given user ID.
     */
    private function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    /**
     * Calculate weekly earnings rank for the user among all astrologers.
     */
    private function calculateWeeklyRank(int $userId): int
    {
        $startOfWeek = Carbon::now()->startOfWeek();

        $rankMap = \App\Helpers\CacheHelper::remember('astrologer:weekly_rank_map', 300, function () use ($startOfWeek) {
            $weeklyRankingsList = DB::table('astrologers')
                ->leftJoin('wallets', 'wallets.user_id', '=', 'astrologers.user_id')
                ->leftJoin('wallet_transactions', function ($join) use ($startOfWeek) {
                    $join->on('wallet_transactions.wallet_id', '=', 'wallets.id')
                        ->where('wallet_transactions.transaction_type', '=', 'credit')
                        ->where('wallet_transactions.status', '=', 'completed')
                        ->where('wallet_transactions.created_at', '>=', $startOfWeek);
                })
                ->select('astrologers.user_id', DB::raw('COALESCE(SUM(wallet_transactions.amount), 0) as weekly_earnings'))
                ->groupBy('astrologers.user_id')
                ->orderByDesc('weekly_earnings')
                ->pluck('astrologers.user_id')
                ->toArray();

            $map = [];
            foreach ($weeklyRankingsList as $index => $uid) {
                $map[$uid] = $index + 1;
            }
            return $map;
        });

        return $rankMap[$userId] ?? 1;
    }

    /**
     * Calculate all-time earnings rank for the user among all astrologers.
     */
    private function calculateAllTimeRank(int $userId): int
    {
        $rankMap = \App\Helpers\CacheHelper::remember('astrologer:alltime_rank_map', 300, function () {
            $allTimeList = DB::table('astrologers')
                ->leftJoin('wallets', 'wallets.user_id', '=', 'astrologers.user_id')
                ->leftJoin('wallet_transactions', function ($join) {
                    $join->on('wallet_transactions.wallet_id', '=', 'wallets.id')
                        ->where('wallet_transactions.transaction_type', '=', 'credit')
                        ->where('wallet_transactions.status', '=', 'completed');
                })
                ->select('astrologers.user_id', DB::raw('COALESCE(SUM(wallet_transactions.amount), 0) as total_earnings'))
                ->groupBy('astrologers.user_id')
                ->orderByDesc('total_earnings')
                ->pluck('astrologers.user_id')
                ->toArray();

            $map = [];
            foreach ($allTimeList as $index => $uid) {
                $map[$uid] = $index + 1;
            }
            return $map;
        });

        return $rankMap[$userId] ?? 1;
    }
}
