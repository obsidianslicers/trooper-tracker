<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Enums\AchievementType;
use App\Facades\TroopTrackerFacade;
use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Displays a trooper's service record dashboard.
 */
class TrooperController extends MagicBusController
{
    /**
     * Retrieves dashboard data and renders the trooper service record view.
     *
     * Filters command staff and handler costumes from the displayed costume list.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request, Trooper $trooper, XenforoService $xenforo): View
    {
        if ($trooper->id == Auth::user()->id)
        {
            $this->crumbs->addRoute('Profile', 'account.profile');
        }

        $service_record_query = new GetTrooperServiceRecordQuery($trooper->id);

        $data = $this->bus->send($service_record_query);

        $trooper_costumes_query = new GetTrooperCostumesQuery($data['trooper']);

        $trooper_costumes = $this->bus->send($trooper_costumes_query);

        $trooper_costumes = $trooper_costumes->filter(fn ($c) => !in_array($c->name, [Costume::COMMAND_STAFF, Costume::HANDLER]));

        $data['trooper_costumes'] = $trooper_costumes;
        $data['xenforo_group_banners'] = collect();
        $data['is_active_donor'] = false;
        $data['xenforo_donations'] = [];

        if (TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($trooper->id);

            if ($xenforo_user_id !== null)
            {
                $group_data = $xenforo->get_user_groups($xenforo_user_id);
                $data['xenforo_group_banners'] = $this->extractXenforoGroupBanners($group_data);

                $upgrade_history = $xenforo->get_user_upgrade_history($xenforo_user_id);
                $data['xenforo_donations'] = $upgrade_history ?? [];
                $data['is_active_donor'] = collect($data['xenforo_donations'])
                    ->contains('is_active', true);

                if (!empty($data['xenforo_donations']))
                {
                    $xenforo_months = $this->computeDonationMonths($data['xenforo_donations']);
                    $data['service_summary']['donation_months'] = $xenforo_months;
                    $data['service_summary']['milestones'] = $this->recomputeSupporterMilestones(
                        $data['service_summary']['milestones'],
                        $xenforo_months
                    );
                }
            }
        }

        return view('pages.service-records.trooper', $data);
    }

    /**
     * Count the distinct calendar months covered by any upgrade record.
     *
     * Iterates month-by-month between start_date and end_date for every
     * upgrade entry so that both monthly and annual subscriptions are counted
     * correctly (e.g. one annual record contributes 12 months).
     *
     * @param  array<int,array<string,mixed>>  $upgrades
     */
    private function computeDonationMonths(array $upgrades): int
    {
        $months = [];
        $now = time();

        foreach ($upgrades as $upgrade)
        {
            $start = (int) ($upgrade['start_date'] ?? 0);
            $end   = (int) ($upgrade['end_date'] ?? 0);

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

        return count($months);
    }

    /**
     * Replace the supporter-month milestones in the milestones array with
     * values derived from XenForo's upgrade history.
     *
     * @param  array<int,array<string,mixed>>  $milestones
     * @return array<int,array<string,mixed>>
     */
    private function recomputeSupporterMilestones(array $milestones, int $xenforo_months): array
    {
        $supporter_types = [
            AchievementType::SUPPORTER_12_MONTHS->value => 12,
            AchievementType::SUPPORTER_24_MONTHS->value => 24,
            AchievementType::SUPPORTER_36_MONTHS->value => 36,
            AchievementType::SUPPORTER_60_MONTHS->value => 60,
        ];

        // Remove any existing supporter milestones so we can replace them.
        $milestones = array_values(array_filter(
            $milestones,
            static fn (array $m): bool => !isset($supporter_types[$m['type']->value])
        ));

        foreach ($supporter_types as $type_value => $threshold)
        {
            if ($xenforo_months >= $threshold)
            {
                $type = AchievementType::from($type_value);

                $milestones[] = [
                    'type'      => $type,
                    'title'     => $type->toTitle(),
                    'icon'      => $type->toIcon(),
                    'is_earned' => true,
                ];
            }
        }

        return $milestones;
    }

    /**
     * @param  array<string,mixed>|null  $group_data
     * @return Collection<int, array{title:string,banner_text:string,is_primary:bool,order:int}>
     */
    private function extractXenforoGroupBanners(?array $group_data): Collection
    {
        return collect($group_data['userGroups'] ?? [])
            ->filter(function (mixed $group): bool {
                return is_array($group) && ! empty($group['bannerText']);
            })
            ->map(function (array $group): array {
                return [
                    'title' => (string) ($group['title'] ?? ''),
                    'banner_text' => (string) ($group['bannerText'] ?? ''),
                    'is_primary' => (bool) ($group['isPrimary'] ?? false),
                    'order' => (int) ($group['order'] ?? PHP_INT_MAX),
                ];
            })
            ->sortBy(function (array $group): string {
                return sprintf(
                    '%d-%010d-%s',
                    $group['is_primary'] ? 0 : 1,
                    $group['order'],
                    strtolower($group['title'])
                );
            })
            ->values();
    }
}
