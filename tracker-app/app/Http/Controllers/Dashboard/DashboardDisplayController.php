<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\MagicBusController;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the display of the main trooper dashboard.
 *
 * This controller gathers various statistics for a trooper, such as troop counts by organization and costume, and displays them.
 */
class DashboardDisplayController extends MagicBusController
{
    /**
     * Handle the incoming request to display the dashboard page for a trooper.
     *
     * Fetches all relevant statistics for a given trooper (or the authenticated user)
     * and displays them on the main dashboard view. Redirects if the trooper is not found.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered dashboard page view.
     */
    public function __invoke(Request $request): View
    {
        $trooper_id = (int) $request->get('trooper_id', Auth::user()->id);

        $trooper = Trooper::with('trooper_achievements')->findOrFail($trooper_id);

        if ($trooper_id == Auth::user()->id)
        {
            $this->crumbs->addRoute('Profile', 'account.profile');
        }

        $metrics = $trooper->trooper_achievements
            ->filter(fn($a) => $a->type->isMetric())
            ->sortBy('display_order');

        $milestones = $trooper->trooper_achievements
            ->filter(fn($a) => $a->type->isMilestone())
            ->sortBy('display_order');

        $data = [
            'trooper' => $trooper,
            'milestones' => $milestones,
            'metrics' => $metrics,
            'total_troops_by_organization' => $this->getTroopsByOrganization($trooper),
            'total_troops_by_costume' => $this->getTroopsByCostume($trooper),
        ];

        return view('pages.dashboard.display', $data);
    }

    /**
     * Retrieves the total troop count for a trooper, grouped by organization.
     *
     * This method only includes organizations the trooper has trooped with and orders
     * them by the number of appearances.
     *
     * @param Trooper $trooper The trooper to calculate statistics for.
     * @return Collection A collection of Organization models, each with a `troop_count` attribute.
     */
    private function getTroopsByOrganization(Trooper $trooper): Collection
    {
        $trooper_id = $trooper->id;

        return Organization::query()
            ->whereHas('organization_costumes.event_troopers', function ($q) use ($trooper_id)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id);
            })
            ->withCount([
                'organization_costumes as troop_count' => function ($q) use ($trooper_id)
                {
                    $q->whereHas('event_troopers', fn($sub) =>
                        $sub->where(EventTrooper::TROOPER_ID, $trooper_id)
                    );
                },
            ])
            ->orderBy('troop_count', 'desc')
            ->get();

    }

    /**
     * Retrieves the total troop count for a trooper, grouped by costume.
     *
     * This method only includes costumes the trooper has actually worn and orders
     * them by the number of appearances. It excludes 'N/A' costumes.
     *
     * @param Trooper $trooper The trooper to calculate statistics for.
     * @return Collection A collection of Costume models, each with a `troop_count` attribute.
     */
    private function getTroopsByCostume(Trooper $trooper): Collection
    {
        $trooper_id = $trooper->id;

        return OrganizationCostume::whereHas('event_troopers', fn($q) => $q->where(EventTrooper::TROOPER_ID, $trooper_id))
            ->with('organization')
            ->withCount([
                'event_troopers as troop_count' => fn($q) =>
                    $q->where(EventTrooper::TROOPER_ID, $trooper_id)
            ])
            ->whereNot(OrganizationCostume::NAME, 'N/A')
            ->orderBy('troop_count', 'desc')
            ->get();
    }
}
