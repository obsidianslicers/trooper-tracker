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
        // First, try to mirror the legacy logic that:
        // - Looks at recurring support records active for the current month based
        //   on start_date and end_date
        // - Adds the matching upgrade cost for each active record
        //
        // Newer payloads expose the useful recurring records in userUpgradeActive,
        // while some older payloads used combinedResults.

        $activeRecords = $stats['userUpgradeActive'] ?? $stats['combinedResults'] ?? null;
        $upgrades = $stats['userUpgrades'] ?? null;

        if (is_array($activeRecords))
        {
            $currentMonth = (int) date('m');
            $currentYear = (int) date('Y');

            $upgradeAmounts = [];

            if (is_array($upgrades))
            {
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

                    $id = (int) $upgrade['user_upgrade_id'];
                    $amount = $this->extract_amount($upgrade);

                    if ($amount > 0.0)
                    {
                        $upgradeAmounts[$id] = $amount;
                    }
                }
            }

            $monthlyTotal = 0.0;

            foreach ($activeRecords as $record)
            {
                if (! is_array($record))
                {
                    continue;
                }

                if (! isset($record['start_date'], $record['end_date'])
                    || ! is_numeric($record['start_date'])
                    || ! is_numeric($record['end_date']))
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

                if (! isset($record['user_upgrade_id']) || ! is_numeric($record['user_upgrade_id']))
                {
                    continue;
                }

                $upgradeId = (int) $record['user_upgrade_id'];
                $recordAmount = $this->extract_amount($record);

                if ($recordAmount <= 0.0 && isset($record['extra']) && is_string($record['extra']))
                {
                    $extra = json_decode($record['extra'], true);

                    if (is_array($extra))
                    {
                        $recordAmount = $this->extract_amount($extra);
                    }
                }

                if ($recordAmount > 0.0)
                {
                    $monthlyTotal += $recordAmount;

                    continue;
                }

                if (isset($upgradeAmounts[$upgradeId]))
                {
                    $monthlyTotal += $upgradeAmounts[$upgradeId];
                }
            }

            if ($monthlyTotal > 0.0)
            {
                return $monthlyTotal;
            }
        }

        // Fallback: generic summing behaviour using the various amount fields
        // in combinedResults, then in paymentLog, then by record count.
        $total = 0.0;

        $records = $stats['combinedResults'] ?? null;

        if (is_array($records))
        {
            foreach ($records as $record)
            {
                if (! is_array($record))
                {
                    continue;
                }

                $total += $this->extract_amount($record);
            }
        }

        if ($total > 0.0)
        {
            return $total;
        }

        $payments = $stats['paymentLog'] ?? null;

        if (is_array($payments))
        {
            foreach ($payments as $record)
            {
                if (! is_array($record))
                {
                    continue;
                }

                $total += $this->extract_amount($record);
            }
        }

        if ($total > 0.0)
        {
            return $total;
        }

        if (is_array($records))
        {
            return (float) count($records);
        }

        return 0.0;
    }

    /**
     * @param  array<string,mixed>  $record
     */
    private function extract_amount(array $record): float
    {
        foreach (['cost_amount', 'payment_gross', 'mc_gross', 'amount', 'total', 'amount_total'] as $key)
        {
            if (isset($record[$key]) && is_numeric($record[$key]))
            {
                return (float) $record[$key];
            }
        }

        if (isset($record['log_details']) && is_array($record['log_details']))
        {
            foreach (['cost_amount', 'payment_gross', 'mc_gross', 'amount', 'total', 'amount_total'] as $key)
            {
                if (isset($record['log_details'][$key]) && is_numeric($record['log_details'][$key]))
                {
                    return (float) $record['log_details'][$key];
                }
            }
        }

        if (isset($record['log_details']) && is_string($record['log_details']))
        {
            $details = json_decode($record['log_details'], true);

            if (is_array($details))
            {
                foreach (['cost_amount', 'payment_gross', 'mc_gross', 'amount', 'total', 'amount_total'] as $key)
                {
                    if (isset($details[$key]) && is_numeric($details[$key]))
                    {
                        return (float) $details[$key];
                    }
                }
            }
        }

        return 0.0;
    }
}
