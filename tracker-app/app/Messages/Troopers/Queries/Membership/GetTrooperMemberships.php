<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Returns the assignments where is_member=true, then the associated identifier
 *
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperMemberships extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {}

    public function handle(): Collection
    {
        $assignments = TrooperAssignment::query()
            ->with(['organization.parent.parent'])
            ->where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        $organizations = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
            ->where(TrooperOrganization::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->get()
            ->keyBy(TrooperOrganization::ORGANIZATION_ID);

        foreach ($assignments as $assignment)
        {
            $primary_organization = $assignment->organization->getPrimaryClub();

            $assignment->membership_path = $this->getMembershipPath($assignment);

            $assignment->organization_membership = $organizations[$primary_organization->id] ?? null;
        }

        return $assignments->filter(fn (TrooperAssignment $trooper_assignment) => $trooper_assignment->organization_membership != null);
    }

    private function getMembershipPath(TrooperAssignment $trooper_assignment): string
    {
        $names = [];

        $organization = $trooper_assignment->organization;

        while ($organization != null)
        {
            $names[] = $organization->name;

            $organization = $organization->parent;
        }

        $names = array_reverse($names);

        return implode(' - ', $names);
    }
}
