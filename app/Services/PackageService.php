<?php

namespace App\Services;

use App\Models\Package;
use App\Models\AstrologerPackage;
use App\Models\PackagePurchase;
use App\Models\Setting;
use App\Models\Wallet;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class PackageService
{
    protected $walletRepo;

    public function __construct(WalletRepository $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    /**
     * Purchase a prepaid package for a user with a specific astrologer.
     *
     * @param int $userId
     * @param int $astrologerId
     * @return PackagePurchase
     * @throws Exception
     */
    public function purchasePackage(int $userId, int $astrologerId): PackagePurchase
    {
        return DB::transaction(function () use ($userId, $astrologerId) {
            // Lock wallets in consistent order to prevent deadlocks
            $firstId = min($userId, $astrologerId);
            $secondId = max($userId, $astrologerId);

            Wallet::firstOrCreate(['user_id' => $firstId], ['balance' => 0]);
            Wallet::firstOrCreate(['user_id' => $secondId], ['balance' => 0]);

            Wallet::where('user_id', $firstId)->lockForUpdate()->first();
            Wallet::where('user_id', $secondId)->lockForUpdate()->first();

            // Find the astrologer's package
            $astroPackage = AstrologerPackage::where('astrologer_id', $astrologerId)->first();
            
            $purchasePrice = 0.00;
            $duration = 0;
            $commissionPct = 0.00;

            if ($astroPackage) {
                $purchasePrice = (float) $astroPackage->amount;
                $duration = (int) $astroPackage->duration;
                $commissionPct = is_null($astroPackage->commission_percentage) 
                    ? (float) Setting::get('global_package_commission_rate', 50.00)
                    : (float) $astroPackage->commission_percentage;
            } else {
                // Fetch system default package
                $defaultPackage = Package::where('is_default', true)->first();
                if (!$defaultPackage) {
                    throw new Exception("No packages defined by the administrator.", 422);
                }
                $purchasePrice = (float) $defaultPackage->default_amount;
                $duration = (int) $defaultPackage->default_duration;
                $commissionPct = (float) Setting::get('global_package_commission_rate', 50.00);
            }

            // Verify user wallet balance
            $userWallet = Wallet::where('user_id', $userId)->first();
            if (!$userWallet || $userWallet->balance < $purchasePrice) {
                throw new Exception("Insufficient balance. Please recharge your wallet to purchase this package.", 422);
            }

            // Calculate earnings split
            $astrologerEarnings = round(($purchasePrice * $commissionPct) / 100, 2);
            $adminEarnings = round($purchasePrice - $astrologerEarnings, 2);

            // Create the purchase record first to get its ID for reference
            $purchase = PackagePurchase::create([
                'user_id' => $userId,
                'astrologer_id' => $astrologerId,
                'total_duration' => $duration,
                'remaining_duration' => $duration,
                'purchase_price' => $purchasePrice,
                'commission_percentage' => $commissionPct,
                'admin_earnings' => $adminEarnings,
                'astrologer_earnings' => $astrologerEarnings,
                'status' => 'active',
            ]);

            // Formulate descriptions
            $astrologer = \App\Models\User::find($astrologerId);
            $userName = \App\Models\User::find($userId)->name ?? 'User';
            $astrologerName = $astrologer->name ?? 'Astrologer';

            // Debit user
            $this->walletRepo->debit(
                $userId,
                $purchasePrice,
                "Prepaid package purchase for Astrologer {$astrologerName}",
                'App\Models\PackagePurchase',
                $purchase->id
            );

            // Credit astrologer immediately
            $this->walletRepo->credit(
                $astrologerId,
                $astrologerEarnings,
                "Prepaid package sale split from User {$userName} ({$commissionPct}%)",
                'App\Models\PackagePurchase',
                $purchase->id
            );

            // Document transaction split in the debit/credit transaction meta records
            $debitTxn = \App\Models\WalletTransaction::where('reference_type', 'App\Models\PackagePurchase')
                ->where('reference_id', $purchase->id)
                ->where('transaction_type', 'debit')
                ->first();
            if ($debitTxn) {
                $debitTxn->update([
                    'meta' => array_merge($debitTxn->meta ?? [], [
                        'split_percentage' => $commissionPct,
                        'base_amount' => $purchasePrice,
                        'admin_earnings' => $adminEarnings,
                        'astrologer_earnings' => $astrologerEarnings,
                    ])
                ]);
            }

            $creditTxn = \App\Models\WalletTransaction::where('reference_type', 'App\Models\PackagePurchase')
                ->where('reference_id', $purchase->id)
                ->where('transaction_type', 'credit')
                ->first();
            if ($creditTxn) {
                $creditTxn->update([
                    'meta' => array_merge($creditTxn->meta ?? [], [
                        'split_percentage' => $commissionPct,
                        'base_amount' => $purchasePrice,
                        'admin_earnings' => $adminEarnings,
                        'astrologer_earnings' => $astrologerEarnings,
                    ])
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Assign default package configuration to a newly registered astrologer.
     *
     * @param int $astrologerId (user_id of the astrologer)
     * @return AstrologerPackage|null
     */
    public function assignDefaultPackage(int $astrologerId): ?AstrologerPackage
    {
        $defaultPackage = Package::where('is_default', true)->first();
        if (!$defaultPackage) {
            return null;
        }

        return AstrologerPackage::firstOrCreate(
            ['astrologer_id' => $astrologerId],
            [
                'amount' => $defaultPackage->default_amount,
                'duration' => $defaultPackage->default_duration,
                'commission_percentage' => null, // defaults to global system setting
            ]
        );
    }
}
