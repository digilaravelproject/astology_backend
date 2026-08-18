<?php

namespace App\Services;

use App\Models\AstrologerPackage;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use App\Services\NotificationHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PackageService
{
    protected WalletRepository $walletRepo;

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
        // 1. Guard against self-purchase
        if ($userId === $astrologerId) {
            throw new Exception("You cannot purchase a package for yourself.", 422);
        }

        // 2. Validate participants exist
        $user = User::find($userId);
        if (!$user) {
            throw new Exception("User not found.", 404);
        }

        $astrologer = User::find($astrologerId);
        if (!$astrologer) {
            throw new Exception("Astrologer not found.", 404);
        }

        $userName       = $user->name ?? 'User';
        $astrologerName = $astrologer->name ?? 'Astrologer';

        // 3. Resolve package pricing and duration specifications
        $packageConfig      = $this->resolvePackagePricing($astrologerId);
        $purchasePrice      = $packageConfig['price'];
        $duration           = $packageConfig['duration'];
        $commissionPct      = $packageConfig['commission_pct'];
        $astrologerEarnings = $packageConfig['astrologer_earnings'];
        $adminEarnings      = $packageConfig['admin_earnings'];

        // 4. Execute atomic database transaction
        $purchase = DB::transaction(function () use (
            $userId,
            $astrologerId,
            $userName,
            $astrologerName,
            $duration,
            $purchasePrice,
            $commissionPct,
            $adminEarnings,
            $astrologerEarnings
        ) {
            // Lock wallets in deterministic order to prevent deadlocks
            $firstId  = min($userId, $astrologerId);
            $secondId = max($userId, $astrologerId);

            Wallet::firstOrCreate(['user_id' => $firstId], ['balance' => 0]);
            Wallet::firstOrCreate(['user_id' => $secondId], ['balance' => 0]);

            Wallet::where('user_id', $firstId)->lockForUpdate()->first();
            Wallet::where('user_id', $secondId)->lockForUpdate()->first();

            // Verify user wallet balance
            $userWallet = Wallet::where('user_id', $userId)->first();
            if (!$userWallet || (float) $userWallet->balance < $purchasePrice) {
                throw new Exception("Insufficient balance. Please recharge your wallet to purchase this package.", 422);
            }

            // Create the active package purchase record
            $purchase = PackagePurchase::create([
                'user_id'               => $userId,
                'astrologer_id'         => $astrologerId,
                'total_duration'        => $duration,
                'remaining_duration'    => $duration,
                'purchase_price'        => $purchasePrice,
                'commission_percentage' => $commissionPct,
                'admin_earnings'        => $adminEarnings,
                'astrologer_earnings'   => $astrologerEarnings,
                'status'                => 'active',
            ]);

            // Ledger: Debit consumer user
            $this->walletRepo->debit(
                $userId,
                $purchasePrice,
                "Prepaid package purchase for Astrologer {$astrologerName}",
                PackagePurchase::class,
                $purchase->id
            );

            // Ledger: Credit astrologer split earnings
            $this->walletRepo->credit(
                $astrologerId,
                $astrologerEarnings,
                "Prepaid package sale split from User {$userName} ({$commissionPct}%)",
                PackagePurchase::class,
                $purchase->id
            );

            // Enrich transaction meta logs
            $metaData = [
                'split_percentage'    => $commissionPct,
                'base_amount'         => $purchasePrice,
                'admin_earnings'      => $adminEarnings,
                'astrologer_earnings' => $astrologerEarnings,
            ];

            WalletTransaction::where('reference_type', PackagePurchase::class)
                ->where('reference_id', $purchase->id)
                ->where('transaction_type', 'debit')
                ->first()
                ?->update(['meta' => $metaData]);

            WalletTransaction::where('reference_type', PackagePurchase::class)
                ->where('reference_id', $purchase->id)
                ->where('transaction_type', 'credit')
                ->first()
                ?->update(['meta' => $metaData]);

            return $purchase;
        });

        // 5. Asynchronous / Background Notification Dispatch (Outside DB Transaction)
        $this->sendPurchaseNotifications(
            purchase: $purchase,
            userName: $userName,
            astrologerName: $astrologerName,
            duration: $duration,
            purchasePrice: $purchasePrice,
            astrologerEarnings: $astrologerEarnings
        );

        return $purchase;
    }

    /**
     * Resolve package pricing, duration, and split calculations.
     *
     * @param int $astrologerId
     * @return array
     * @throws Exception
     */
    protected function resolvePackagePricing(int $astrologerId): array
    {
        $astroPackage = AstrologerPackage::where('astrologer_id', $astrologerId)->first();

        if ($astroPackage && (float) $astroPackage->amount > 0 && (int) $astroPackage->duration > 0) {
            $purchasePrice = (float) $astroPackage->amount;
            $duration      = (int) $astroPackage->duration;
            $commissionPct = is_null($astroPackage->commission_percentage)
                ? (float) Setting::get('global_package_commission_rate', 50.00)
                : (float) $astroPackage->commission_percentage;
        } else {
            $defaultPackage = Package::where('is_default', true)->first();
            if (!$defaultPackage) {
                throw new Exception("No default package defined by the administrator.", 422);
            }
            $purchasePrice = (float) $defaultPackage->default_amount;
            $duration      = (int) $defaultPackage->default_duration;
            $commissionPct = (float) Setting::get('global_package_commission_rate', 50.00);
        }

        $astrologerEarnings = round(($purchasePrice * $commissionPct) / 100, 2);
        $adminEarnings      = round($purchasePrice - $astrologerEarnings, 2);

        return [
            'price'               => $purchasePrice,
            'duration'            => $duration,
            'commission_pct'      => $commissionPct,
            'astrologer_earnings' => $astrologerEarnings,
            'admin_earnings'      => $adminEarnings,
        ];
    }

    /**
     * Safely dispatch push and in-app notifications to User and Astrologer on package purchase.
     *
     * @param PackagePurchase $purchase
     * @param string $userName
     * @param string $astrologerName
     * @param int $duration
     * @param float $purchasePrice
     * @param float $astrologerEarnings
     */
    protected function sendPurchaseNotifications(
        PackagePurchase $purchase,
        string $userName,
        string $astrologerName,
        int $duration,
        float $purchasePrice,
        float $astrologerEarnings
    ): void {
        try {
            $durationMins = (int) round($duration / 60);

            // 1. Notify Consumer User
            NotificationHelper::send(
                userId: (int) $purchase->user_id,
                title: 'Package Purchased! 📦',
                body: "You bought a {$durationMins}-minute consultation package for {$astrologerName} for ₹" . number_format($purchasePrice, 2) . ".",
                meta: [
                    'type'             => 'package',
                    'package_id'       => (string) $purchase->id,
                    'astrologer_id'    => (string) $purchase->astrologer_id,
                    'astrologer_name'  => $astrologerName,
                    'duration_seconds' => (string) $duration,
                    'duration_minutes' => (string) $durationMins,
                    'amount'           => (string) $purchasePrice,
                    'screen_route'     => '/package-status',
                ]
            );

            // 2. Notify Astrologer
            NotificationHelper::send(
                userId: (int) $purchase->astrologer_id,
                title: 'New Package Booking! 📦',
                body: "{$userName} purchased a {$durationMins}-minute consultation package with you. (₹" . number_format($astrologerEarnings, 2) . " credited).",
                meta: [
                    'type'             => 'package',
                    'package_id'       => (string) $purchase->id,
                    'user_id'          => (string) $purchase->user_id,
                    'user_name'        => $userName,
                    'duration_seconds' => (string) $duration,
                    'duration_minutes' => (string) $durationMins,
                    'amount_credited'  => (string) $astrologerEarnings,
                    'screen_route'     => '/wallet',
                ]
            );
        } catch (Throwable $ne) {
            Log::error('Package purchase notification failed: ' . $ne->getMessage(), [
                'purchase_id' => $purchase->id,
                'user_id'     => $purchase->user_id,
            ]);
        }
    }

    /**
     * Assign default package configuration to a newly registered astrologer.
     *
     * @param int $astrologerId (user_id of the astrologer)
     * @return AstrologerPackage|null
     */
    public function assignDefaultPackage(int $astrologerId): ?AstrologerPackage
    {
        try {
            $defaultPackage = Package::where('is_default', true)->first();
            if (!$defaultPackage) {
                return null;
            }

            return AstrologerPackage::firstOrCreate(
                ['astrologer_id' => $astrologerId],
                [
                    'amount'                => $defaultPackage->default_amount,
                    'duration'              => $defaultPackage->default_duration,
                    'commission_percentage' => null, // defaults to global system setting
                ]
            );
        } catch (Throwable $e) {
            Log::error('Assign default package failed for astrologer #' . $astrologerId . ': ' . $e->getMessage());
            return null;
        }
    }
}
