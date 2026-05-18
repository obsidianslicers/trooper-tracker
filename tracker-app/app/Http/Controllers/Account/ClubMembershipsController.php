<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the club memberships page for the authenticated trooper.
 *
 * Shows leaf-node organizations the trooper can request to join,
 * excluding ones they're already a member of or have a pending request for.
 */
class ClubMembershipsController extends MagicBusController
{
    /**
     * @param  Request  $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $available_clubs = $this->bus->send(new GetAvailableClubsQuery($trooper));

        $current_clubs = Organization::whereHas('trooper_assignments', fn ($q) => $q
                ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                ->where(TrooperAssignment::IS_MEMBER, true)
            )
            ->orderBy(Organization::SEQUENCE)
            ->get();

        // Load all ancestors in one query for unlimited-depth path display
        $ancestor_ids = $current_clubs->flatMap(
            fn ($c) => array_filter(explode(Organization::NODE_PATH_SEP, trim($c->node_path, Organization::NODE_PATH_SEP)))
        )->unique()->values()->toArray();

        $ancestors = Organization::whereIn(Organization::ID, $ancestor_ids)
            ->get([Organization::ID, Organization::NAME])
            ->keyBy(Organization::ID);

        $available_clubs_data = $available_clubs->map(fn ($org) => [
            'id'                    => $org->id,
            'name'                  => $org->name,
            'parent_id'             => $org->parent_id,
            'depth'                 => $org->depth,
            'identifier_display'    => $org->identifier_display,
            'identifier_validation' => $org->identifier_validation,
        ]);

        $data = compact('available_clubs', 'available_clubs_data', 'current_clubs', 'ancestors');

        return view('pages.account.club-memberships', $data);
    }
}
