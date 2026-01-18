<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Adds a costume to the trooper's profile via HTMX.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Receives HTMX request with organization_id and costume_id
 * - **Domain (Models):** Validates membership, then uses Trooper::attachCostume() to add costume
 * - **Responder:** Returns costumes table HTML fragment for HTMX swap
 *
 * Security validation:
 * 1. Verifies trooper is an active member of the organization (via withActiveTroopers scope)
 * 2. Verifies costume belongs to that organization
 * 3. Only then calls attachCostume() to create/restore TrooperCostume record
 *
 * The attachCostume() method handles duplicates intelligently:
 * - Creates new record if costume not previously added
 * - Restores soft-deleted record if costume was previously removed
 *
 * This enables dynamic costume assignment without full page reload.
 */
class CostumesSubmitHtmxController extends MagicBusController
{
    /**
     * Add a costume to the authenticated trooper's profile.
     *
     * Validates that:
     * - organization_id and costume_id are provided and valid
     * - Trooper is an active member of the organization
     * - Costume belongs to the specified organization
     *
     * Then attaches the costume and returns updated costume table HTML.
     *
     * @param Request $request The incoming HTTP request containing organization_id and costume_id.
     * @return View The costumes table partial for HTMX replacement.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organization_id = (int) $request->input('organization_id', -1);
        $costume_id = (int) $request->input('costume_id', -1);

        if ($organization_id > -1 && $costume_id > -1)
        {
            $organization = Organization::withActiveTroopers($trooper->id)
                ->where(Organization::ID, $organization_id)
                ->first();

            if (isset($organization))
            {
                $costume = $organization->organization_costumes()
                    ->where(OrganizationCostume::ID, $costume_id)
                    ->first();

                if (isset($costume))
                {
                    $trooper_costume = $trooper->trooper_costumes()
                        ->withTrashed()
                        ->where(TrooperCostume::COSTUME_ID, $costume_id)
                        ->first();

                    if ($trooper_costume === null)
                    {
                        $trooper->trooper_costumes()->create([TrooperCostume::COSTUME_ID => $costume_id]);
                    }
                    else
                    {
                        $trooper_costume->restore();
                    }
                }
            }
        }

        $trooper_costumes_query = new GetTrooperCostumesQuery($trooper);

        $trooper_costumes = $this->bus->send($trooper_costumes_query);

        $data = compact('trooper_costumes');

        return view('pages.account.costumes-table', $data);
    }
}
