<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays service record leaderboard metrics.
 */
class LeaderboardController extends MagicBusController
{
    /**
     * Retrieves leaderboard metrics for a lookback window and renders the leaderboard view.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): View
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
                Organization::NODE_PATH,
            ]);

        $organization_id = $request->integer('organization_id') ?: null;
        $organization = $organization_id
            ? $organizations->firstWhere(Organization::ID, $organization_id)
            : null;

        if ($organization === null)
        {
            $organization_id = null;
        }

        $leaderboard = $this->bus->send(new GetLeaderboardMetricsQuery($days, $organization, 30));

        $costume_list = Costume::whereNotIn(Costume::NAME, ['N/A', 'NA', Costume::HANDLER, Costume::COMMAND_STAFF])
            ->orderBy(Costume::NAME)
            ->get([Costume::ID, Costume::NAME]);

        $data = compact('leaderboard', 'days', 'organizations', 'organization_id', 'organization', 'costume_list');

        return view('pages.service-records.leaderboard', $data);
    }

    private function resolveDays(Request $request): ?int
    {
        if (!$request->has('days') || $request->query('days') === 'all')
        {
            return null;
        }

        $days = $request->integer('days');

        return in_array($days, [30, 60, 90, 180, 360], true)
            ? $days
            : null;
    }
}
