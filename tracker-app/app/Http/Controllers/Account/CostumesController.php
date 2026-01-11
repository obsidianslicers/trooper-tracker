<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationCostume;
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
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organizations = Organization::withActiveTroopers($trooper->id)->get();

        $selected_organization = null;
        $costumes = [];
        $trooper->trooper_costumes->load('organization_costume.organization');

        if ($request->has('organization_id'))
        {
            $organization_id = $request->get('organization_id');

            $selected_organization = $organizations->firstWhere(Organization::ID, $organization_id);

            if (isset($selected_organization))
            {
                $assigned_costume_ids = $trooper->trooper_costumes
                    ->filter(fn($tc) => $tc->organization_costume?->organization_id === $selected_organization->id)
                    ->map(fn($tc) => $tc->organization_costume->id)
                    ->filter() // remove nulls
                    ->values();

                $costumes = $selected_organization->organization_costumes()
                    ->with('organization')
                    ->excluding($assigned_costume_ids)
                    ->orderBy(OrganizationCostume::NAME)
                    ->toOptions(OrganizationCostume::NAME, OrganizationCostume::ID);
            }
        }

        $trooper_costumes = $trooper->trooper_costumes;

        $data = [
            'organizations' => $organizations,
            'selected_organization' => $selected_organization,
            'costumes' => $costumes,
            'trooper_costumes' => $trooper_costumes,
        ];

        return view('pages.account.costumes', $data);
    }
}
