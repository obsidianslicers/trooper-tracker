<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the trooper organization membership setup page.
 *
 * This controller retrieves the authenticated trooper's organization assignments
 * and renders the setup page where troopers can configure their organization
 * memberships and select which regions/units they belong to.
 */
class SetupController extends MagicBusController
{
    /**
     * Handle the incoming request to display the organization setup page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered organization setup page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $assignments_query = new GetTrooperAssignmentsQuery($trooper);

        $organization_memberships = $this->bus->send(message: $assignments_query);

        $data = compact('trooper', 'organization_memberships');

        return view('pages.account.setup', $data);
    }
}
