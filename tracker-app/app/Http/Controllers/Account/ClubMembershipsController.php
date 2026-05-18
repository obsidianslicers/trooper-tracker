<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperJoinRequest;
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

        $pending_requests = TrooperJoinRequest::query()
            ->where(TrooperJoinRequest::TROOPER_ID, $trooper->id)
            ->pending()
            ->with('organization')
            ->orderBy(TrooperJoinRequest::CREATED_AT, 'desc')
            ->get();

        // Also include ancestors for pending request organizations
        $pending_ancestor_ids = $pending_requests->flatMap(
            fn ($r) => array_filter(explode(Organization::NODE_PATH_SEP, trim($r->organization->node_path, Organization::NODE_PATH_SEP)))
        )->toArray();

        $ancestor_ids = array_unique(array_merge($ancestor_ids, $pending_ancestor_ids));

        $ancestors = Organization::whereIn(Organization::ID, $ancestor_ids)
            ->get([Organization::ID, Organization::NAME])
            ->keyBy(Organization::ID);

        // Fetch root org identifier rules in one query so children can inherit them
        $root_org_ids = $available_clubs->map(function ($org) {
            $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));

            return (int) reset($parts);
        })->unique()->toArray();

        $root_orgs = Organization::whereIn(Organization::ID, $root_org_ids)
            ->get([Organization::ID, Organization::IDENTIFIER_DISPLAY, Organization::IDENTIFIER_VALIDATION])
            ->keyBy(Organization::ID);

        $available_clubs_data = $available_clubs->map(function ($org) use ($root_orgs) {
            $parts  = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));
            $root   = $root_orgs[(int) reset($parts)] ?? null;

            return [
                'id'                    => $org->id,
                'name'                  => $org->name,
                'parent_id'             => $org->parent_id,
                'depth'                 => $org->depth,
                'identifier_display'    => $org->identifier_display ?? $root?->identifier_display,
                'identifier_validation' => $org->identifier_validation ?? $root?->identifier_validation,
            ];
        });

        $data = compact('available_clubs', 'available_clubs_data', 'current_clubs', 'ancestors', 'pending_requests');

        return view('pages.account.club-memberships', $data);
    }
}
