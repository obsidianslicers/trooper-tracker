<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Organizations\Queries\GetOrganizationCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the costume management page for the authenticated trooper.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Retrieves authenticated trooper and their organizations
 * - **Domain (Services):** Uses GetOrganizationCostumesQuery to fetch available costumes
 * - **Responder:** Renders costume management view with dropdown and trooper's costumes
 *
 * The page displays:
 * 1. Dropdown of available costumes from organizations the trooper is a member of
 * 2. Table of costumes already added to the trooper's profile
 * 3. HTMX-enabled add/delete buttons for dynamic updates
 *
 * In Star Wars costuming clubs, troopers track approved costumes (e.g., TK-421 Stormtrooper,
 * Darth Vader) assigned to them from their organizations. This page manages that relationship.
 */
class CostumesController extends MagicBusController
{
    /**
     * Display the costume management page for the authenticated trooper.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Loads organizations the trooper is an active member of
     * 3. Dispatches GetOrganizationCostumesQuery to fetch available costumes from those organizations
     * 4. Dispatches GetTrooperCostumesQuery to fetch trooper's currently assigned costumes
     * 5. Renders the costume management page with both costume lists
     *
     * @param Request $request The incoming HTTP request containing authenticated trooper
     * @return View The costume management page (pages.account.costumes) with organization_costumes and trooper_costumes
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organization_ids = Organization::withActiveTroopers($trooper->id)
            ->pluck(Organization::ID)
            ->toArray();

        $organization_costumes_query = new GetOrganizationCostumesQuery($organization_ids);

        $organization_costumes = $this->bus->send($organization_costumes_query);

        $trooper_costumes_query = new GetTrooperCostumesQuery($trooper);

        $trooper_costumes = $this->bus->send($trooper_costumes_query);

        $data = compact('organization_costumes', 'trooper_costumes');

        return view('pages.account.costumes', $data);
    }
}
