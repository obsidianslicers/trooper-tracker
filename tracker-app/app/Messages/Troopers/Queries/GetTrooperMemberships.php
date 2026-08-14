<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use App\Models\TrooperAssignment;
use Symfony\Component\String\TruncateMode;

/**
 * Returns the assignments where is_member=true, then the associated identifier
 * 
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperMemberships extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {
    }

    public function handle(): Collection
    {
        $assignments = TrooperAssignment::query()
            ->with(['organization.parent.parent'])
            ->where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get();

        $organizations = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
            ->get()
            ->keyBy(TrooperOrganization::ORGANIZATION_ID);

        foreach ($assignments as $assignment)
        {
            $primary_organization = $assignment->organization->getPrimaryClub();

            $assignment->membership_path = $this->getMembershipPath($assignment);

            $assignment->organization_membership = $organizations[$primary_organization->id] ?? null;
        }

        return $assignments->filter(fn(TrooperAssignment $trooper_assignment) => $trooper_assignment->organization_memberhip != null);
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

        rsort($names);

        return implode(' - ', $names);
    }
}
