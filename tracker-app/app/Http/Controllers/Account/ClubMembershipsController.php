<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Displays the club memberships page for the authenticated trooper.
 *
 * Shows leaf-node organizations the trooper can request to join,
 * excluding ones they're already a member of or have a pending request for.
 */
class ClubMembershipsController extends MagicBusController
{
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

        $pending_requests = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->pending()
            ->with('organization')
            ->orderBy(TrooperOrganization::CREATED_AT, 'desc')
            ->get();

        foreach ($current_clubs as $current_club)
        {
            $primary_club = $current_club->getPrimaryClub();

            $current_club->is_pending = $pending_requests->contains(function ($pr) use ($primary_club) {
                if ($pr->organization_id === $primary_club->id)
                {
                    return $pr->membership_status === MembershipStatus::PENDING;
                }

                return false;
            });
        }

        $ancestors = $this->loadAncestors($current_clubs, $pending_requests);
        $available_clubs_data = $this->buildAvailableClubsData($available_clubs);

        $data = compact('available_clubs', 'available_clubs_data', 'current_clubs', 'ancestors', 'pending_requests', 'trooper');

        return view('pages.account.club-memberships', $data);
    }

    private function loadAncestors(Collection $current_clubs, Collection $pending_requests): Collection
    {
        $parse = fn ($path) => array_filter(explode(Organization::NODE_PATH_SEP, trim($path, Organization::NODE_PATH_SEP)));

        $ancestor_ids = $current_clubs->flatMap(fn ($c) => $parse($c->node_path))->unique()->values()->toArray();

        $pending_ancestor_ids = $pending_requests->flatMap(fn ($r) => $parse($r->organization->node_path))->toArray();

        $all_ids = array_unique(array_merge($ancestor_ids, $pending_ancestor_ids));

        return Organization::whereIn(Organization::ID, $all_ids)
            ->get([Organization::ID, Organization::NAME])
            ->keyBy(Organization::ID);
    }

    private function buildAvailableClubsData(Collection $available_clubs): Collection
    {
        $root_org_ids = $available_clubs->map(function ($org) {
            $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));

            return (int) reset($parts);
        })->unique()->toArray();

        $root_orgs = Organization::whereIn(Organization::ID, $root_org_ids)
            ->get([Organization::ID, Organization::IDENTIFIER_DISPLAY, Organization::IDENTIFIER_VALIDATION])
            ->keyBy(Organization::ID);

        return $available_clubs->map(function ($org) use ($root_orgs) {
            $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));
            $root = $root_orgs[(int) reset($parts)] ?? null;

            return [
                'id' => $org->id,
                'name' => $org->name,
                'parent_id' => $org->parent_id,
                'depth' => $org->depth,
                'identifier_display' => $org->identifier_display ?? $root?->identifier_display,
                'identifier_validation' => $org->identifier_validation ?? $root?->identifier_validation,
            ];
        });
    }
}
