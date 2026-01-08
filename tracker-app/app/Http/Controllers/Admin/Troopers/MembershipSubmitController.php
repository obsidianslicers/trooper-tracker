<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Handles the display of a trooper's membership management page.
 *
 * Presents organizational memberships and related assignments for a given trooper
 * so administrators and moderators can review or adjust membership details.
 */
class MembershipSubmitController extends Controller
{
    /**
     * Handle the incoming request to display a trooper's authority page.
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for managing a specific trooper's roles and organizational assignments.
     *
     * @param MembershipRequest $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose authorities are to be displayed.
     * @return View|RedirectResponse The rendered authority page view or a redirect response.
     */
    public function __invoke(MembershipRequest $request, Trooper $trooper): View|RedirectResponse
    {
        $this->authorize('update', $trooper);

        if ($request->isHtmx())
        {
            // TODO update the view based on picks
            $organization_memberships = $this->getOrganizationMemberships($trooper);

            $data = compact('trooper', 'organization_memberships');

            return view('pages.admin.troopers.membership', $data);
        }

        //  otherwise, save & redirect back to the main edit page

        return redirect()->route('admin.troopers.membership', compact('trooper'));
    }

    /**
     * Build a collection of organizations with inferred region/unit assignments for a trooper.
     *
     * Loads the trooper's organizations, finds member assignments, and attaches
     * the resolved region/unit nodes onto each top-level organization for display purposes.
     *
     * @param Trooper $trooper The trooper whose memberships should be fetched.
     * @return Collection The organizations with optional `region` and `unit` properties hydrated.
     */
    private function getOrganizationMemberships(Trooper $trooper): Collection
    {
        $organizations = Organization::ofTypeOrganizations()->orderBy('name')->get();

        $organization_memberships = $trooper->organizations()->pluck('tt_trooper_organizations.identifier', 'tt_organizations.id')->toArray();

        $assignments = $trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            if (isset($organization_memberships[$organization->id]) === false)
            {
                continue;
            }

            $organization->identifier = $organization_memberships[$organization->id];

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
