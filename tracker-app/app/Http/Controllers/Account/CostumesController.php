<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Organizations\GetOrganizationCostumesQuery;
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
class CostumesController extends Controller
{
    /**
     * Display the costume management page for the authenticated trooper.
     *
     * Retrieves:
     * - Organizations the trooper is an active member of
     * - Available costumes from those organizations (via GetOrganizationCostumesQuery)
     * - Trooper's currently assigned costumes with eager-loaded relationships
     *
     * @param Request $request The incoming HTTP request containing authenticated trooper.
     * @param GetOrganizationCostumesQuery $get_organization_costumes Service to fetch organization costumes.
     * @return View The costume management page with organization_costumes and trooper_costumes.
     */
    public function __invoke(
        Request $request,
        GetOrganizationCostumesQuery $get_organization_costumes): View
    {
        $trooper = $request->user();

        $organizations = Organization::withActiveTroopers($trooper->id)
            ->pluck(Organization::ID)
            ->toArray();

        $organization_costumes = $get_organization_costumes($organizations);

        $trooper_costumes = $trooper->trooper_costumes()->with('organization_costume.organization')->get();

        $data = compact('organization_costumes', 'trooper_costumes');

        return view('pages.account.costumes', $data);
    }
}
