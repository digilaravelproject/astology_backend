<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletRepository
{
    public function findByUserId($userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    public function debit($userId, $amount, $description, $reference_type = null, $reference_id = null): bool
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference_type, $reference_id) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $amount) {
                return false;
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance -= $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'description' => $description,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);

            return true;
        });
    }

    public function credit($userId, $amount, $description, $reference_type = null, $reference_id = null): bool
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference_type, $reference_id) {
            $wallet = Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            // Re-fetch with lock
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

            $balanceBefore = $wallet->balance;
            $wallet->balance += $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'description' => $description,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);

            return true;
        });
    }
}
