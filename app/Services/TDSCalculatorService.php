<?php

namespace App\Services;

use App\Models\Setting;

/**
 * TDSCalculatorService
 *
 * Handles Tax Deducted at Source (TDS) calculations for Astrologer payouts.
 */
class TDSCalculatorService
{
    /**
     * Retrieve current platform TDS configuration.
     */
    public function getTdsSettings(): array
    {
        return [
            'tds_enabled'   => (bool) Setting::get('tds_enabled', true),
            'tds_rate'      => (float) Setting::get('tds_rate', 10.00),
            'tds_threshold' => (float) Setting::get('tds_threshold', 0.00),
        ];
    }

    /**
     * Calculate TDS breakdown for a given gross settlement amount.
     */
    public function calculatePayoutTDS(float $grossAmount): array
    {
        $settings = $this->getTdsSettings();
        $gross = round(max(0, $grossAmount), 2);

        $isTdsApplicable = $settings['tds_enabled'] && ($gross >= $settings['tds_threshold']);

        if ($isTdsApplicable) {
            $tdsPercent = round($settings['tds_rate'], 2);
            $tdsAmount = round(($gross * $tdsPercent) / 100, 2);
            $netPaid = round($gross - $tdsAmount, 2);
        } else {
            $tdsPercent = 0.00;
            $tdsAmount = 0.00;
            $netPaid = $gross;
        }

        return [
            'gross_amount'       => $gross,
            'is_tds_applicable'  => $isTdsApplicable,
            'tds_percent'        => $tdsPercent,
            'tds_amount'         => $tdsAmount,
            'net_paid_amount'    => $netPaid,
        ];
    }
}
