<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\TrooperDonation;
use App\Services\Forums\XenforoService;
use Illuminate\Support\Facades\Config;

class SupportStatusService
{
    private XenforoService $xenforo_service;

    public function __construct(XenforoService $xenforo_service)
    {
        $this->xenforo_service = $xenforo_service;
    }

    /**
     * Calculate the current support status for the tracker.
     *
     * When XenForo is configured, this prefers the XenForo upgrade stats
     * endpoint. Otherwise, it falls back to local TrooperDonation records.
     *
     * @return array{goal:float,current:float,progress:float,uses_xenforo:bool}
     */
    public function calculate(): array
    {
        $goal = (float) Config::get('tracker.support.goal', 0.0);

        if ($goal <= 0.0)
        {
            return [
                'goal' => 0.0,
                'current' => 0.0,
                'progress' => 0.0,
                'uses_xenforo' => false,
            ];
        }

        $stats = $this->xenforo_service->get_upgrade_stats();

        if (is_array($stats))
        {
            $current = $this->calculate_from_xenforo($stats);

            if ($current > 0.0)
            {
                $progress = min(100.0, ($current / $goal) * 100.0);

                return [
                    'goal' => $goal,
                    'current' => $current,
                    'progress' => $progress,
                    'uses_xenforo' => true,
                ];
            }
        }

        $donations = (float) TrooperDonation::forMonth()->sum(TrooperDonation::AMOUNT);
        $progress = 0.0;

        if ($goal > 0.0)
        {
            $progress = min(100.0, ($donations / $goal) * 100.0);
        }

        return [
            'goal' => $goal,
            'current' => $donations,
            'progress' => $progress,
            'uses_xenforo' => false,
        ];
    }

    /**
     * @param  array<string,mixed>  $stats
     */
    private function calculate_from_xenforo(array $stats): float
    {
        $activeRecords = $stats['userUpgradeActive'] ?? null;
        $upgrades = $stats['userUpgrades'] ?? null;

        if (! is_array($activeRecords) || ! is_array($upgrades))
        {
            return 0.0;
        }

        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');
        $upgradeAmounts = [];

        foreach ($upgrades as $upgrade)
        {
            if (! is_array($upgrade))
            {
                continue;
            }

            if (! isset($upgrade['user_upgrade_id']) || ! is_numeric($upgrade['user_upgrade_id']))
            {
                continue;
            }

            $amount = $this->extract_cost_amount($upgrade);

            if ($amount <= 0.0)
            {
                continue;
            }

            $upgradeAmounts[(int) $upgrade['user_upgrade_id']] = $amount;
        }

        $monthlyTotal = 0.0;

        foreach ($activeRecords as $record)
        {
            if (! is_array($record))
            {
                continue;
            }

            if (! isset($record['start_date'], $record['end_date'], $record['user_upgrade_id'])
                || ! is_numeric($record['start_date'])
                || ! is_numeric($record['end_date'])
                || ! is_numeric($record['user_upgrade_id']))
            {
                continue;
            }

            $startTs = (int) $record['start_date'];
            $endTs = (int) $record['end_date'];

            $startMonth = (int) date('m', $startTs);
            $startYear = (int) date('Y', $startTs);
            $endMonth = (int) date('m', $endTs);
            $endYear = (int) date('Y', $endTs);

            $inRange = (
                ($startYear < $currentYear
                    || ($startYear === $currentYear && $startMonth <= $currentMonth))
                && ($endYear > $currentYear
                    || ($endYear === $currentYear && $endMonth >= $currentMonth))
            );

            if (! $inRange)
            {
                continue;
            }

            $amount = $this->extract_cost_amount($record);

            if ($amount <= 0.0 && isset($record['extra']) && is_string($record['extra']))
            {
                $extra = json_decode($record['extra'], true);

                if (is_array($extra))
                {
                    $amount = $this->extract_cost_amount($extra);
                }
            }

            if ($amount <= 0.0)
            {
                $amount = $upgradeAmounts[(int) $record['user_upgrade_id']] ?? 0.0;
            }

            $monthlyTotal += $amount;
        }

        return $monthlyTotal;
    }

    /**
     * @param  array<string,mixed>  $record
     */
    private function extract_cost_amount(array $record): float
    {
        return isset($record['cost_amount']) && is_numeric($record['cost_amount'])
            ? (float) $record['cost_amount']
            : 0.0;
    }
}
