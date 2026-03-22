<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Facades\TroopTrackerFacade;
use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
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

        if (TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($trooper->id);

            if ($xenforo_user_id !== null)
            {
                $group_data = $xenforo->get_user_groups($xenforo_user_id);
                $data['xenforo_group_banners'] = $this->extractXenforoGroupBanners($group_data);
            }
        }

        return view('pages.service-records.trooper', $data);
    }

    /**
     * @param  array<string,mixed>|null  $group_data
     * @return Collection<int, array{title:string,banner_text:string,is_primary:bool,order:int}>
     */
    private function extractXenforoGroupBanners(?array $group_data): Collection
    {
        return collect($group_data['userGroups'] ?? [])
            ->filter(function (mixed $group): bool
            {
                return is_array($group) && ! empty($group['bannerText']);
            })
            ->map(function (array $group): array
            {
                return [
                    'title' => (string) ($group['title'] ?? ''),
                    'banner_text' => (string) ($group['bannerText'] ?? ''),
                    'is_primary' => (bool) ($group['isPrimary'] ?? false),
                    'order' => (int) ($group['order'] ?? PHP_INT_MAX),
                ];
            })
            ->sortBy(function (array $group): string
            {
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
