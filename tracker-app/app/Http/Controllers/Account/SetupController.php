<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Handles the display of the trooper setup page for organization management.
 *
 * Presents the authenticated trooper with their enrolled organizations and member
 * assignments (regions/units), allowing them to review or configure their organizational
 * memberships and associated hierarchy.
 */
class SetupController extends Controller
{
    /**
     * Handle the incoming request to display the trooper setup page.
     *
     * Retrieves the authenticated trooper, assembles organizations with resolved
     * region/unit assignments based on member status, and renders the setup view.
     *
     * @param Request $request The incoming HTTP request (may contain 'region_id' query param).
     * @return View The rendered setup page view.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organizations = $this->getOrganizationsForTrooper($request, $trooper);

        $data = compact('trooper', 'organizations');

        return view('pages.account.setup', $data);
    }

    /**
     * Build a collection of organizations with resolved region/unit assignments for a trooper.
     *
     * Loads all organizations the trooper is enrolled in (via TrooperOrganization pivot),
     * marks selections, resolves member assignments to region/unit nodes, and handles
     * single-region shortcut logic or request-filtered region selection.
     *
     * @param Request $request The incoming HTTP request (may contain 'region_id' query param).
     * @param Trooper $trooper The trooper whose organizations are being fetched.
     * @return Collection Organizations with `selected`, `region`, and `unit` attributes hydrated.
     */
    private function getOrganizationsForTrooper(Request $request, Trooper $trooper): Collection
    {
        $with = [
            'organizations.organizations',
            'troopers' => function ($query) use ($trooper)
            {
                $query->where('trooper_id', $trooper->id);
            },
        ];

        $organizations = Organization::with($with)
            ->ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $trooper->trooper_assignments()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            $organization->selected = $organization->troopers->isNotEmpty();

            if ($organization->organizations->count() == 1)
            {
                $organization->region = $organization->organizations->first();
            }
            elseif ($request->has('region_id'))
            {
                $organization->region = $organization->organizations
                    ->filter(fn($r) => $r->id == $request->input('region_id'))
                    ->first();
            }

            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    if ($assignment->organization->type == OrganizationType::UNIT)
                    {
                        $organization->unit = $assignment->organization;
                        $organization->region = $organization->unit->parent;
                    }
                    elseif ($assignment->organization->type == OrganizationType::REGION)
                    {
                        $organization->region = $assignment->organization;
                    }
                }
            }
        }


        return $organizations;
    }
}
