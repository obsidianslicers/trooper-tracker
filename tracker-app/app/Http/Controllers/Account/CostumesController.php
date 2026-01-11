<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Services\Organizations\GetOrganizationCostumesQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the trooper costumes management interface via an HTMX request.
 */
class CostumesController extends Controller
{
    /**
     * Handle the incoming request to display the trooper costumes interface.
     *
     * This method fetches the user's assigned organizations and their trooper costumes.
     * If a 'organization_id' is provided, it also fetches the available costumes for that specific organization.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered trooper costumes view.
     */
    public function __invoke(
        Request $request,
        GetOrganizationCostumesQuery $get_organization_costumes): View
    {
        $trooper = $request->user();

        $organizations = Organization::withActiveTroopers($trooper->id)
            ->pluck(Organization::ID)
            ->toArray();

        // $selected_organization = null;
        $organization_costumes = $get_organization_costumes($organizations);

        $trooper_costumes = $trooper->trooper_costumes()->with('organization_costume.organization')->get();

        $data = compact('organization_costumes', 'trooper_costumes');

        return view('pages.account.costumes', $data);
    }
}
