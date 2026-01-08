<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

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

        $organization_memberships = $this->getOrganizationMemberships($trooper);

        $data = compact('trooper', 'organization_memberships');

        return view('pages.account.setup', $data);
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
        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        $assignments = $trooper->trooper_assignments()
            ->with('organization')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            foreach ($assignments as $assignment)
            {
                if (str_starts_with($assignment->organization->node_path, $organization->node_path))
                {
                    $organization->assignment = $assignment->organization;
                }
            }
        }

        return $organizations;
    }
}
