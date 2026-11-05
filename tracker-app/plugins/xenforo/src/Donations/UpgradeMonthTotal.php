<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Donations;

use TroopTracker\Plugins\Xenforo\Support\XenforoUpgradeHelper;

/**
 * This month's total from the forum's upgrade stats: every active or expired
 * upgrade that covers the current calendar month, at its cost.
 */
final class UpgradeMonthTotal
{
    /**
     * @param  array<string,mixed>  $stats
     */
    public static function fromStats(array $stats): float
    {
        $active_records = $stats['userUpgradeActive'] ?? null;
        $expired_records = $stats['userUpgradeExpired'] ?? null;
        $upgrades = $stats['userUpgrades'] ?? null;

        if (!is_array($upgrades))
        {
            return 0.0;
        }

        $records = [];

        if (is_array($active_records))
        {
            $records = array_merge($records, $active_records);
        }

        if (is_array($expired_records))
        {
            $records = array_merge($records, $expired_records);
        }

        if (empty($records))
        {
            return 0.0;
        }

        $upgrade_amounts = self::buildUpgradeCostMap($upgrades);
        $monthly_total = 0.0;

        foreach ($records as $record)
        {
            if (!is_array($record))
            {
                continue;
            }

            if (!isset($record['start_date'], $record['end_date'], $record['user_upgrade_id'])
                || !is_numeric($record['start_date'])
                || !is_numeric($record['end_date'])
                || !is_numeric($record['user_upgrade_id']))
            {
                continue;
            }

            if (!self::isRecordInCurrentMonth((int) $record['start_date'], (int) $record['end_date']))
            {
                continue;
            }

            $monthly_total += XenforoUpgradeHelper::resolveRecordCost($record, $upgrade_amounts);
        }

        return $monthly_total;
    }

    /**
     * Build a lookup of user_upgrade_id → cost_amount from upgrade definitions.
     *
     * @param  array<mixed>  $upgrades
     * @return array<int,float>
     */
    private static function buildUpgradeCostMap(array $upgrades): array
    {
        $upgrade_amounts = [];

        foreach ($upgrades as $upgrade)
        {
            if (!is_array($upgrade))
            {
                continue;
            }

            if (!isset($upgrade['user_upgrade_id']) || !is_numeric($upgrade['user_upgrade_id']))
            {
                continue;
            }

            $amount = self::extractCostAmount($upgrade);

            if ($amount <= 0.0)
            {
                continue;
            }

            $upgrade_amounts[(int) $upgrade['user_upgrade_id']] = $amount;
        }

        return $upgrade_amounts;
    }

    /**
     * Determine whether a subscription record covers the current calendar month.
     */
    private static function isRecordInCurrentMonth(int $start_ts, int $end_ts): bool
    {
        $current_month = (int) date('m');
        $current_year = (int) date('Y');
        $start_month = (int) date('m', $start_ts);
        $start_year = (int) date('Y', $start_ts);
        $end_month = (int) date('m', $end_ts);
        $end_year = (int) date('Y', $end_ts);

        return ($start_year < $current_year
                || ($start_year === $current_year && $start_month <= $current_month))
            && ($end_year > $current_year
                || ($end_year === $current_year && $end_month >= $current_month));
    }

    /**
     * @param  array<string,mixed>  $record
     */
    private static function extractCostAmount(array $record): float
    {
        return isset($record['cost_amount']) && is_numeric($record['cost_amount'])
            ? (float) $record['cost_amount']
            : 0.0;
    }
}
