<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\AstrologerBankAccount;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AstrologerWalletService
{
    /**
     * Get wallet balance and key earnings metrics for the astrologer.
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Calculate earnings metrics (completed credits)
        $todayEarning = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $weeklyEarning = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->sum('amount');

        $monthlyEarning = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');

        $threeMonthEarning = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->sum('amount');

        // Calculate rank based on all-time completed credit earnings
        $earningsList = DB::table('astrologers')
            ->leftJoin('wallets', 'wallets.user_id', '=', 'astrologers.user_id')
            ->leftJoin('wallet_transactions', function($join) {
                $join->on('wallet_transactions.wallet_id', '=', 'wallets.id')
                     ->where('wallet_transactions.transaction_type', '=', 'credit')
                     ->where('wallet_transactions.status', '=', 'completed');
            })
            ->select('astrologers.user_id', DB::raw('COALESCE(SUM(wallet_transactions.amount), 0) as total_earnings'))
            ->groupBy('astrologers.user_id')
            ->orderByDesc('total_earnings')
            ->get();

        $rank = 1;
        foreach ($earningsList as $index => $earning) {
            if ($earning->user_id == $user->id) {
                $rank = $index + 1;
                break;
            }
        }

        return [
            'total_balance' => (float)$wallet->balance,
            'today_earning' => (float)$todayEarning,
            'weekly_earning' => (float)$weeklyEarning,
            'monthly_earning' => (float)$monthlyEarning,
            'three_month_earning' => (float)$threeMonthEarning,
            'rank' => $rank,
        ];
    }

    /**
     * Get filtered earning history for the astrologer.
     */
    public function getEarningsHistory(User $user, ?string $filter = null)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

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
     * Get withdrawal history for the astrologer.
     */
    public function getWithdrawalHistory(User $user)
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'debit')
            ->where(function($q) {
                $q->where('description', 'like', '%Withdrawal%')
                  ->orWhere('description', 'like', '%payout%');
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * Submit a withdrawal request.
     */
    public function requestWithdrawal(User $user, float $amount, int $bankAccountId): array
    {
        $astrologer = $user->astrologer;
        if (!$astrologer) {
            throw new Exception('Unauthorized. This account is not registered as an astrologer.', 403);
        }

        $bankAccount = AstrologerBankAccount::where('astrologer_id', $astrologer->id)
            ->where('id', $bankAccountId)
            ->where('is_active', true)
            ->first();

        if (!$bankAccount) {
            throw new Exception('Invalid or inactive bank account selected.', 422);
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        // Check pending withdrawals to avoid overdraft
        $pendingWithdrawalSum = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('transaction_type', 'debit')
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = (float)$wallet->balance - (float)$pendingWithdrawalSum;

        if ($amount > $availableBalance) {
            throw new Exception("Insufficient available balance. Your total balance is ₹{$wallet->balance}, but you have ₹{$pendingWithdrawalSum} in pending withdrawals.", 422);
        }

        // Create pending debit transaction
        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'Withdrawal Request',
            'meta' => [
                'bank_account_id' => $bankAccount->id,
                'account_holder_name' => $bankAccount->account_holder_name,
                'bank_name' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
                'ifsc_code' => $bankAccount->ifsc_code,
                'requested_at' => now()->toDateTimeString(),
            ],
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance,
        ]);

        return [
            'transaction' => $transaction,
            'available_balance' => $availableBalance - $amount,
        ];
    }

    /**
     * Get weekly ranking for astrologers based on completed credit earnings for the current week.
     */
    public function getWeeklyRankings(User $user): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();

        // Get list of all astrologers and calculate their earnings for the current week
        $rankings = DB::table('astrologers')
            ->join('users', 'users.id', '=', 'astrologers.user_id')
            ->leftJoin('wallets', 'wallets.user_id', '=', 'astrologers.user_id')
            ->leftJoin('wallet_transactions', function($join) use ($startOfWeek) {
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

        // Find the rank and earning of the logged-in user
        $myRank = null;
        $myEarnings = 0.00;

        foreach ($rankings as $index => $ranking) {
            $ranking->weekly_earnings = (float) $ranking->weekly_earnings;
            if ($ranking->user_id == $user->id) {
                $myRank = $index + 1;
                $myEarnings = $ranking->weekly_earnings;
            }
        }

        // Return top 10 and my info
        $top10 = $rankings->take(10)->values()->map(function ($item, $index) {
            return [
                'rank' => $index + 1,
                'astrologer_id' => $item->astrologer_id,
                'user_id' => $item->user_id,
                'name' => $item->name,
                'profile_photo' => $item->profile_photo,
                'weekly_earnings' => $item->weekly_earnings,
            ];
        });

        return [
            'top_astrologers' => $top10,
            'my_rank' => $myRank ?? (count($rankings) + 1),
            'my_weekly_earnings' => $myEarnings,
        ];
    }
}
