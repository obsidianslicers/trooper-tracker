<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Handles the display of the main account management page.
 *
 * This controller is responsible for fetching the authenticated user's
 * data and rendering the primary account view where they can manage
 * their profile, settings, and other account-related information.
 */
class SetupController extends Controller
{
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the currently authenticated trooper and renders
     * the main account management view, passing the trooper's data to it.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered account page view.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organizations = $this->getOrganizationsForTrooper($request, $trooper);

        $data = compact('trooper', 'organizations');

        return view('pages.account.setup', $data);
    }

    private function getOrganizationsForTrooper(Request $request, Trooper $trooper): Collection
    {
        $with = [
            'organizations.organizations',
            'troopers' => function ($query) use ($trooper)
            {
                $query->where('trooper_id', $trooper->id);
            },
        ];

        $organizations = Organization::with($with)
            ->ofTypeOrganizations()
            ->orderBy(Organization::NAME)
            ->get();

        $assignments = $trooper->trooper_assignments()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        foreach ($organizations as $organization)
        {
            $organization->selected = $organization->troopers->isNotEmpty();

            if ($organization->organizations->count() == 1)
            {
                $organization->region = $organization->organizations->first();
            }
            elseif ($request->has('region_id'))
            {
                $organization->region = $organization->organizations
                    ->filter(fn($r) => $r->id == $request->input('region_id'))
                    ->first();
            }

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
