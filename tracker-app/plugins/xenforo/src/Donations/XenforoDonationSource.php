<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Donations;

use App\Contracts\DonationSource;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Support\DonationSummary;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;
use TroopTracker\Plugins\Xenforo\Support\XenforoUpgradeHelper;

/**
 * Forum account upgrades (paid subscriptions) count as donations.
 *
 * The recalculation fetches every member's upgrades in one API call per run and
 * prices each month at the upgrade's cost. The service record uses the member's
 * live upgrade history and its totals.
 */
final class XenforoDonationSource implements DonationSource
{
    /**
     * Upgrade rows keyed by forum user ID, loaded on first use.
     *
     * @var array<int, array<int, array<string, mixed>>>|null
     */
    private ?array $upgrades_by_user = null;

    public function __construct(
        private readonly XenforoService $xenforo,
        private readonly XenforoProfile $profile,
    ) {}

    public function summariesFor(array $trooper_ids): array
    {
        if (!XenforoService::isConfigured() || $trooper_ids === [])
        {
            return [];
        }

        $upgrades_by_user = $this->upgradesByUser();
        $summaries = [];

        foreach ($this->forumUserIds($trooper_ids) as $trooper_id => $user_id)
        {
            if (isset($upgrades_by_user[$user_id]))
            {
                $summaries[$trooper_id] = $this->summarize($upgrades_by_user[$user_id]);
            }
        }

        return $summaries;
    }

    public function summaryFor(Trooper $trooper): ?DonationSummary
    {
        $history = $this->profile->upgradeHistory($trooper);

        if ($history === [])
        {
            return null;
        }

        return new DonationSummary(
            month_keys: XenforoUpgradeHelper::monthKeysFromUpgrades($history, []),
            total: (float) collect($history)->sum('total_amount'),
            is_active: collect($history)->contains('is_active', true),
        );
    }

    /**
     * @param  array<int, int>  $trooper_ids
     * @return array<int, int> Forum user ID keyed by trooper ID.
     */
    private function forumUserIds(array $trooper_ids): array
    {
        return OauthLogin::where(OauthLogin::PROVIDER, XenforoLoginProvider::KEY)
            ->whereIn(OauthLogin::TROOPER_ID, $trooper_ids)
            ->pluck(OauthLogin::PROVIDER_ID, OauthLogin::TROOPER_ID)
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function summarize(array $rows): DonationSummary
    {
        $total = 0.0;
        $is_active = false;

        foreach ($rows as $row)
        {
            $is_active = $is_active || ($row['is_active'] ?? false);
            $cost = (float) ($row['cost_amount'] ?? 0);
            $start = (int) ($row['start_date'] ?? 0);

            if ($start > 0 && $cost > 0.0)
            {
                $end = (int) ($row['end_date'] ?? 0);
                $months = XenforoUpgradeHelper::countMonthsForRecord($start, $end);
                $total += $months * $cost;
            }
        }

        return new DonationSummary(
            month_keys: XenforoUpgradeHelper::monthKeysFromUpgrades($rows, []),
            total: $total,
            is_active: $is_active,
        );
    }

    public function monthTotal(): ?float
    {
        if (!XenforoService::isConfigured())
        {
            return 0.0;
        }

        $stats = $this->xenforo->get_upgrade_stats();

        return $stats === null ? null : UpgradeMonthTotal::fromStats($stats);
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function upgradesByUser(): array
    {
        return $this->upgrades_by_user ??= $this->fetchUpgrades();
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function fetchUpgrades(): array
    {
        $stats = $this->xenforo->get_upgrade_stats();

        if ($stats === null)
        {
            return [];
        }

        $cost_map = $this->costMap($stats);
        $by_user = [];

        foreach (['userUpgradeActive' => true, 'userUpgradeExpired' => false] as $key => $is_active)
        {
            foreach ($stats[$key] ?? [] as $row)
            {
                if (is_array($row) && isset($row['user_id']))
                {
                    $row['cost_amount'] = XenforoUpgradeHelper::resolveRecordCost($row, $cost_map);
                    $row['is_active'] = $is_active;
                    $by_user[(int) $row['user_id']][] = $row;
                }
            }
        }

        return $by_user;
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return array<int, float>
     */
    private function costMap(array $stats): array
    {
        $cost_map = [];

        foreach ($stats['userUpgrades'] ?? [] as $upgrade)
        {
            if (is_array($upgrade) && isset($upgrade['user_upgrade_id']))
            {
                $cost = (float) ($upgrade['cost_amount'] ?? 0);
                $cost_map[(int) $upgrade['user_upgrade_id']] = $cost;
            }
        }

        return $cost_map;
    }
}
