<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Facades\TroopTrackerFacade;
use App\Models\TrooperDonation;
use App\Services\Forums\XenforoService;
use App\Support\XenforoUpgradeHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SupportStatusService
{
    public const CACHE_KEY = 'support_status';

    private const CACHE_TTL = 900;

    /**
     * Calculate the current support status for the tracker.
     *
     * When XenForo is configured, this prefers the XenForo upgrade stats
     * endpoint. Otherwise, it falls back to local TrooperDonation records.
     *
     * Results are cached for 15 minutes, except when the XenForo fetch
     * fails - a transient outage should not pin the local fallback total.
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

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached))
        {
            return $cached;
        }

        $xenforo_configured = TroopTrackerFacade::isXenforoIntegrationConfigured();
        $stats = $xenforo_configured ? app(XenforoService::class)->get_upgrade_stats() : null;
        $status = $this->buildStatus($goal, $stats);

        if (!$xenforo_configured || is_array($stats))
        {
            Cache::put(self::CACHE_KEY, $status, self::CACHE_TTL);
        }

        return $status;
    }

    /**
     * Build the support status from XenForo stats or local donations.
     *
     * @param  array<string,mixed>|null  $stats
     * @return array{goal:float,current:float,progress:float,uses_xenforo:bool}
     */
    private function buildStatus(float $goal, ?array $stats): array
    {
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

        $upgrade_amounts = $this->buildUpgradeCostMap($upgrades);
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
    private function extractCostAmount(array $record): float
    {
        return isset($record['cost_amount']) && is_numeric($record['cost_amount'])
            ? (float) $record['cost_amount']
            : 0.0;
    }
}
