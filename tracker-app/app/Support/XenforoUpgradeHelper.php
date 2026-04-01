<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

/**
 * Stateless utility for XenForo upgrade record calculations.
 *
 * Centralises three algorithms that are shared across
 * RecalculateTrooperRankCommandHandler, XenforoService, and SupportStatusService:
 *
 *  - resolveRecordCost   — extract per-period cost from a record
 *  - countMonthsForRecord — count calendar months a single record spans
 *  - monthKeysFromUpgrades — aggregate distinct months across many records
 */
final class XenforoUpgradeHelper
{
    /**
     * Resolve the per-period cost for a single upgrade record.
     *
     * Priority:
     *   1. Record's own `extra` JSON (actual cost at subscription time)
     *   2. Upgrade definition fallback via cost_map
     *
     * @param  array<string,mixed>  $row
     * @param  array<int,float>  $cost_map  user_upgrade_id → cost_amount from definitions
     */
    public static function resolveRecordCost(array $row, array $cost_map): float
    {
        if (isset($row['extra']) && is_string($row['extra']))
        {
            $extra = json_decode($row['extra'], true);

            if (is_array($extra) && isset($extra['cost_amount']) && is_numeric($extra['cost_amount']))
            {
                $amount = (float) $extra['cost_amount'];

                if ($amount > 0.0)
                {
                    return $amount;
                }
            }
        }

        return $cost_map[(int) ($row['user_upgrade_id'] ?? 0)] ?? 0.0;
    }

    /**
     * Count the number of distinct calendar months a single upgrade record spans.
     *
     * An end_date of 0 or any future timestamp is capped to the current time
     * so active subscriptions are counted only up to today.
     *
     * Returns 0 when start_date is missing or invalid.
     */
    public static function countMonthsForRecord(int $start_date, int $end_date): int
    {
        if ($start_date <= 0)
        {
            return 0;
        }

        $now = time();

        if ($end_date === 0 || $end_date > $now)
        {
            $end_date = $now;
        }

        $current   = Carbon::createFromTimestamp($start_date)->startOfMonth();
        $end_month = Carbon::createFromTimestamp($end_date)->startOfMonth();
        $count     = 0;

        while ($current->lte($end_month))
        {
            $count++;
            $current->addMonth();
        }

        return $count;
    }

    /**
     * Return distinct calendar month keys (Y-m => true) covered by a set of upgrade records.
     *
     * Merges active and expired records, de-duplicates overlapping month ranges.
     *
     * @param  array<mixed>  $active
     * @param  array<mixed>  $expired
     * @return array<string,true>  e.g. ['2024-09' => true, '2024-10' => true, ...]
     */
    public static function monthKeysFromUpgrades(array $active, array $expired): array
    {
        $months = [];
        $now    = time();

        foreach (array_merge($active, $expired) as $row)
        {
            $start = (int) ($row['start_date'] ?? 0);
            $end   = (int) ($row['end_date'] ?? 0);

            if ($start <= 0)
            {
                continue;
            }

            if ($end === 0 || $end > $now)
            {
                $end = $now;
            }

            $current   = Carbon::createFromTimestamp($start)->startOfMonth();
            $end_month = Carbon::createFromTimestamp($end)->startOfMonth();

            while ($current->lte($end_month))
            {
                $months[$current->format('Y-m')] = true;
                $current->addMonth();
            }
        }

        return $months;
    }
}
