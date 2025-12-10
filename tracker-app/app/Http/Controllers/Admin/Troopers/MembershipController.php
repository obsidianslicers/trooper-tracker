<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Handles the display of a trooper's membership management page.
 *
 * Presents organizational memberships and related assignments for a given trooper
 * so administrators and moderators can review or adjust membership details.
 */
class MembershipController extends Controller
{
    /**
     * Create a new MembershipController instance and seed breadcrumb trail.
     *
     * @param BreadCrumbService $crumbs The breadcrumb service for managing navigation history.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's authority page.
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for managing a specific trooper's roles and organizational assignments.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose authorities are to be displayed.
     * @return View The rendered authority page view.
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $organization_memberships = $this->getOrganizationMemberships($trooper);

        $data = compact('trooper', 'organization_memberships');

        return view('pages.admin.troopers.membership', $data);
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
        $organizations = $trooper->organizations()->orderBy('name')->get();

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
