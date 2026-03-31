<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\TrooperDonation;
use App\Services\Forums\XenforoService;
use App\Support\XenforoUpgradeHelper;
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
            $current = $this->calculateFromXenforo($stats);

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
    private function calculateFromXenforo(array $stats): float
    {
        $active_records = $stats['userUpgradeActive'] ?? null;
        $upgrades       = $stats['userUpgrades'] ?? null;

        if (!is_array($active_records) || !is_array($upgrades))
        {
            return 0.0;
        }

        $upgrade_amounts = $this->buildUpgradeCostMap($upgrades);
        $monthly_total   = 0.0;

        foreach ($active_records as $record)
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

            if (!$this->isRecordInCurrentMonth((int) $record['start_date'], (int) $record['end_date']))
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
    private function buildUpgradeCostMap(array $upgrades): array
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

            $amount = $this->extractCostAmount($upgrade);

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
    private function isRecordInCurrentMonth(int $start_ts, int $end_ts): bool
    {
        $current_month = (int) date('m');
        $current_year  = (int) date('Y');
        $start_month   = (int) date('m', $start_ts);
        $start_year    = (int) date('Y', $start_ts);
        $end_month     = (int) date('m', $end_ts);
        $end_year      = (int) date('Y', $end_ts);

        return ($start_year < $current_year
                || ($start_year === $current_year && $start_month <= $current_month))
            && ($end_year > $current_year
                || ($end_year === $current_year && $end_month >= $current_month));
    }

    /**
     * @param  array<string,mixed>  $record
     */
    private function extractCostAmount(array $record): float
    {
        return isset($record['cost_amount']) && is_numeric($record['cost_amount'])
            ? (float) $record['cost_amount']
            : 0.0;
    }
}
