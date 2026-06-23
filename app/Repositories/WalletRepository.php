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

    public function debit($userId, $amount, $description, $reference_type = null, $reference_id = null): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference_type, $reference_id) {
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \Exception("Wallet not found for user ID: {$userId}");
            }
            if ($wallet->balance < $amount) {
                throw new \Exception("Insufficient balance in user wallet.");
            }

            $resolvedDescription = $description;
            $meta = [];

            if ($reference_type && $reference_id) {
                try {
                    if (class_exists($reference_type)) {
                        if (str_contains($reference_type, 'SuperChat')) {
                            $refModel = $reference_type::with(['user', 'astrologer.user'])->find($reference_id);
                            if ($refModel) {
                                $userName = $refModel->user->name ?? 'User';
                                $astrologerName = $refModel->astrologer?->user?->name ?? 'Astrologer';
                                $resolvedDescription = "Super Chat from {$userName} to Astrologer {$astrologerName}";
                                $meta = [
                                    'type' => 'super_chat',
                                    'user_id' => $refModel->user_id,
                                    'user_name' => $userName,
                                    'astrologer_id' => $refModel->astrologer_id,
                                    'astrologer_name' => $astrologerName,
                                    'live_session_id' => $refModel->live_session_id,
                                ];
                            }
                        } else {
                            $refModel = $reference_type::with(['consumer', 'provider'])->find($reference_id);
                            if ($refModel) {
                                $sessionType = '';
                                if (str_contains($reference_type, 'ChatSession')) {
                                    $sessionType = 'Chat';
                                } elseif (str_contains($reference_type, 'CallSession')) {
                                    $sessionType = 'Call';
                                } else {
                                    $sessionType = 'Consultation';
                                }

                                $refName = $sessionType . ' session reference #' . $reference_id;
                                $providerName = $refModel->provider->name ?? 'Astrologer';
                                $resolvedDescription = "{$sessionType} session with Astrologer {$providerName}";
                                $meta = [
                                    'type' => strtolower($sessionType),
                                    'astrologer_id' => $refModel->provider_id,
                                    'astrologer_name' => $providerName,
                                    'session_id' => $reference_id,
                                    'session_reference' => $refName,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback
                }
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance -= $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'status' => 'completed',
                'description' => $resolvedDescription,
                'meta' => $meta,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);
        });
    }

    public function credit($userId, $amount, $description, $reference_type = null, $reference_id = null): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference_type, $reference_id) {
            $wallet = Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            // Re-fetch with lock
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \Exception("Wallet not found for user ID: {$userId}");
            }

            $resolvedDescription = $description;
            $meta = [];

            if ($reference_type && $reference_id) {
                try {
                    if (class_exists($reference_type)) {
                        if (str_contains($reference_type, 'SuperChat')) {
                            $refModel = $reference_type::with(['user', 'astrologer.user'])->find($reference_id);
                            if ($refModel) {
                                $userName = $refModel->user->name ?? 'User';
                                $astrologerName = $refModel->astrologer?->user?->name ?? 'Astrologer';
                                $resolvedDescription = "Super Chat from {$userName} to Astrologer {$astrologerName}";
                                $meta = [
                                    'type' => 'super_chat',
                                    'user_id' => $refModel->user_id,
                                    'user_name' => $userName,
                                    'astrologer_id' => $refModel->astrologer_id,
                                    'astrologer_name' => $astrologerName,
                                    'live_session_id' => $refModel->live_session_id,
                                ];
                            }
                        } else {
                            $refModel = $reference_type::with(['consumer', 'provider'])->find($reference_id);
                            if ($refModel) {
                                $sessionType = '';
                                if (str_contains($reference_type, 'ChatSession')) {
                                    $sessionType = 'Chat';
                                } elseif (str_contains($reference_type, 'CallSession')) {
                                    $sessionType = 'Call';
                                } else {
                                    $sessionType = 'Consultation';
                                }

                                $refName = $sessionType . ' session reference #' . $reference_id;
                                $consumerName = $refModel->consumer->name ?? 'User';
                                $resolvedDescription = "{$sessionType} consultation with User {$consumerName}";
                                $meta = [
                                    'type' => strtolower($sessionType),
                                    'user_id' => $refModel->consumer_id,
                                    'user_name' => $consumerName,
                                    'session_id' => $reference_id,
                                    'session_reference' => $refName,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback
                }
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance += $amount;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'status' => 'completed',
                'description' => $resolvedDescription,
                'meta' => $meta,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
            ]);
        });
    }

    public function transferForSuperChat(int $userId, int $astrologerUserId, float $amount, \App\Models\SuperChat $superChat): array
    {
        $firstUserId = min($userId, $astrologerUserId);
        $secondUserId = max($userId, $astrologerUserId);

        if ($firstUserId === $secondUserId) {
            Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            $userWallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
            $astrologerWallet = $userWallet;
        } else {
            Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
            Wallet::firstOrCreate(['user_id' => $astrologerUserId], ['balance' => 0]);

            $firstWallet = Wallet::where('user_id', $firstUserId)->lockForUpdate()->first();
            $secondWallet = Wallet::where('user_id', $secondUserId)->lockForUpdate()->first();

            $userWallet = $userId === $firstUserId ? $firstWallet : $secondWallet;
            $astrologerWallet = $userId === $firstUserId ? $secondWallet : $firstWallet;
        }

        if (!$userWallet) {
            throw new \Exception("Wallet not found for user ID: {$userId}");
        }
        if ($userWallet->balance < $amount) {
            throw new \Exception("Insufficient balance in user wallet.", 402);
        }
        if (!$astrologerWallet) {
            throw new \Exception("Wallet not found for astrologer ID: {$astrologerUserId}");
        }

        $superChat->loadMissing(['user', 'astrologer.user']);

        $userName = $superChat->user->name ?? 'User';
        $astrologerName = $superChat->astrologer?->user?->name ?? 'Astrologer';
        $description = "Super Chat from {$userName} to Astrologer {$astrologerName}";
        
        $meta = [
            'type' => 'super_chat',
            'user_id' => $superChat->user_id,
            'user_name' => $userName,
            'astrologer_id' => $superChat->astrologer_id,
            'astrologer_name' => $astrologerName,
            'live_session_id' => $superChat->live_session_id,
        ];

        // Process debit
        $userBalanceBefore = $userWallet->balance;
        $userWallet->balance -= $amount;
        $userWallet->save();

        $debitTxn = WalletTransaction::create([
            'wallet_id' => $userWallet->id,
            'transaction_type' => 'debit',
            'amount' => $amount,
            'status' => 'completed',
            'description' => $description,
            'meta' => $meta,
            'balance_before' => $userBalanceBefore,
            'balance_after' => $userWallet->balance,
            'reference_type' => 'App\Models\SuperChat',
            'reference_id' => $superChat->id,
        ]);

        // Process credit
        $astroBalanceBefore = $astrologerWallet->balance;
        $astrologerWallet->balance += $amount;
        $astrologerWallet->save();

        $creditTxn = WalletTransaction::create([
            'wallet_id' => $astrologerWallet->id,
            'transaction_type' => 'credit',
            'amount' => $amount,
            'status' => 'completed',
            'description' => $description,
            'meta' => $meta,
            'balance_before' => $astroBalanceBefore,
            'balance_after' => $astrologerWallet->balance,
            'reference_type' => 'App\Models\SuperChat',
            'reference_id' => $superChat->id,
        ]);

        return [
            'debit' => $debitTxn,
            'credit' => $creditTxn,
        ];
    }
}
