<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Http\Controllers\MagicBusController;
use App\Services\Troopers\GetTrooperOrganizationMembershipsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the trooper organization membership setup page.
 *
 * This controller follows the ADR pattern as an Action that:
 * - Retrieves the authenticated trooper's organization assignments
 * - Loads organizations with hierarchical structure (Org → Region → Unit)
 * - Marks current member assignments via GetTrooperAssignmentsQuery
 * - Renders the setup page where troopers can configure their organization memberships
 *
 * Troopers select which organizations they belong to and which regions/units
 * they are actively assigned to within each organization.
 */
class SetupController extends MagicBusController
{
    /**
     * Handle the incoming request to display the organization setup page.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Dispatches GetTrooperAssignmentsQuery to load organizations with assignment data
     * 3. Renders the setup page with organization memberships for trooper configuration
     *
     * @param Request $request The incoming HTTP request containing the authenticated trooper
     * @return View The rendered setup page view (pages.account.setup)
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
