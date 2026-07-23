<?php

namespace App\Services;

use App\Models\Astrologer;
use App\Models\Setting;
use Carbon\Carbon;

class PricingCalculatorService
{
    /**
     * Calculate price and commission split for a session.
     *
     * @param Astrologer $astrologer
     * @param string $sessionType ('chat' or 'call')
     * @return array
     */
    public function calculate(Astrologer $astrologer, string $sessionType): array
    {
        // 1. Get base price
        $basePrice = $sessionType === 'chat' 
            ? (float) $astrologer->chat_rate_per_minute 
            : (float) $astrologer->call_rate_per_minute;

        // 2. Fetch currently active, unexpired offer
        $activeOfferPivot = null;
        if ($astrologer->relationLoaded('offers')) {
            $activeOfferPivot = $astrologer->offers->first();
        } else {
            $activeOfferPivot = $astrologer->offers()
                ->wherePivot('status', 'active')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                })
                ->first();
        }

        // 3. Fallback logic if no active offer exists
        if (!$activeOfferPivot) {
            $globalAdminRate = (float) Setting::get('global_admin_commission_rate', 20.00);
            $adminShareAmount = ($basePrice * $globalAdminRate) / 100;
            $astrologerShareAmount = $basePrice - $adminShareAmount;

            return [
                'has_offer' => false,
                'offer_id' => null,
                'customer_rate' => $basePrice,
                'original_rate' => $basePrice,
                'astrologer_share_percentage' => 100 - $globalAdminRate,
                'astrologer_payout' => round($astrologerShareAmount, 2),
                'admin_share_percentage' => $globalAdminRate,
                'admin_revenue' => round($adminShareAmount, 2),
            ];
        }

        // 4. Offer-based logic
        $offer = $activeOfferPivot;
        $discount = (float) $offer->discount_percentage;
        $customerPrice = $basePrice * (1 - ($discount / 100));

        $astrologerSharePct = $sessionType === 'chat' 
            ? (float) $offer->chat_astrologer_share 
            : (float) $offer->call_astrologer_share;

        $adminSharePct = $sessionType === 'chat' 
            ? (float) $offer->chat_admin_share 
            : (float) $offer->call_admin_share;

        $astrologerPayout = ($customerPrice * $astrologerSharePct) / 100;
        $adminRevenue = ($customerPrice * $adminSharePct) / 100;

        return [
            'has_offer' => true,
            'offer_id' => $offer->id,
            'customer_rate' => round($customerPrice, 2),
            'original_rate' => $basePrice,
            'astrologer_share_percentage' => $astrologerSharePct,
            'astrologer_payout' => round($astrologerPayout, 2),
            'admin_share_percentage' => $adminSharePct,
            'admin_revenue' => round($adminRevenue, 2),
        ];
    }
}
