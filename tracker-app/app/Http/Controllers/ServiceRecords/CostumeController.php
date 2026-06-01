<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Reports\Queries\GetCostumeTrooperLeaderboardQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CostumeController extends MagicBusController
{
    public function __invoke(Request $request, Costume $costume): View
    {
        $days = $this->resolveDays($request);

        $organizations = Organization::ofTypeOrganizations()
            ->whereNull(Organization::PARENT_ID)
            ->orderBy(Organization::NAME)
            ->get([
                Organization::ID,
                Organization::NAME,
                Organization::IMAGE_PATH_SM,
                Organization::IMAGE_PATH_LG,
            ]);

        $organization_id = $request->integer('organization_id') ?: null;
        $organization = $organization_id
            ? $organizations->firstWhere(Organization::ID, $organization_id)
            : null;

        if ($organization === null)
        {
            $organization_id = null;
        }

        $result = $this->bus->send(
            new GetCostumeTrooperLeaderboardQuery($costume, $days, $organization, 30)
        );

        $top_troopers = $result['top_troopers'];
        $stats = $result['stats'];

        $data = compact(
            'costume',
            'top_troopers',
            'stats',
            'days',
            'organizations',
            'organization_id',
            'organization',
        );

        return view('pages.service-records.costume', $data);
    }

    private function resolveDays(Request $request): ?int
    {
        if (!$request->has('days') || $request->query('days') === 'all')
        {
            return null;
        }

        $days = $request->integer('days');

        return in_array($days, [30, 60, 90, 180, 360], true) ? $days : null;
    }
}
