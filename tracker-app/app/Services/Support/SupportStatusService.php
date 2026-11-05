<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Plugins\PluginGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class SupportStatusService
{
    public const CACHE_KEY = 'support_status';

    private const CACHE_TTL = 900;

    /**
     * Calculate the current support status for the tracker.
     *
     * Prefers this month's total from the donation sources plugins add, such
     * as forum upgrades. Without one, or when they total nothing, it falls back
     * to local TrooperDonation records.
     *
     * Results are cached for 15 minutes, except when a source fails to fetch -
     * a transient outage should not pin the local fallback total.
     *
     * @return array{goal:float,current:float,progress:float,uses_donation_sources:bool}
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
                'uses_donation_sources' => false,
            ];
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached))
        {
            return $cached;
        }

        $source_total = $this->sourcesMonthTotal();
        $status = $this->buildStatus($goal, $source_total ?? 0.0);

        if ($source_total !== null)
        {
            Cache::put(self::CACHE_KEY, $status, self::CACHE_TTL);
        }

        return $status;
    }

    /**
     * This month's total across every donation source, or null if any failed.
     */
    private function sourcesMonthTotal(): ?float
    {
        $total = 0.0;

        foreach (app(PluginDonations::class)->sources() as $source)
        {
            $month_total = PluginGuard::attempt(
                'total this month\'s donations',
                fn (): ?float => $source->monthTotal(),
            );

            if ($month_total === null)
            {
                return null;
            }

            $total += $month_total;
        }

        return $total;
    }

    /**
     * Build the support status from the donation sources or local donations.
     *
     * @return array{goal:float,current:float,progress:float,uses_donation_sources:bool}
     */
    private function buildStatus(float $goal, float $source_total): array
    {
        if ($source_total > 0.0)
        {
            return [
                'goal' => $goal,
                'current' => $source_total,
                'progress' => min(100.0, ($source_total / $goal) * 100.0),
                'uses_donation_sources' => true,
            ];
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
            'uses_donation_sources' => false,
        ];
    }
}
