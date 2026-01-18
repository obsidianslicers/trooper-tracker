<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Http\Controllers\MagicBusController;
use App\Services\Troopers\GetTrooperOrganizationMembershipsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the trooper setup page for organization management.
 *
 * Presents the authenticated trooper with their enrolled organizations and member
 * assignments (regions/units), allowing them to review or configure their organizational
 * memberships and associated hierarchy.
 */
class SetupController extends MagicBusController
{
    /**
     * Handle the incoming request to display the trooper setup page.
     *
     * Retrieves the authenticated trooper, assembles organizations with resolved
     * region/unit assignments based on member status, and renders the setup view.
     *
     * @param Request $request The incoming HTTP request (may contain 'region_id' query param).
     * @param GetTrooperOrganizationMembershipsQuery $get_trooper_organizations The service to get trooper organization memberships.
     * @return View The rendered setup page view.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $get_assignments = new GetTrooperAssignmentsQuery($trooper);

        $organization_memberships = $this->bus->send(message: $get_assignments);

        $data = compact('trooper', 'organization_memberships');

        return view('pages.account.setup', $data);
    }
}
