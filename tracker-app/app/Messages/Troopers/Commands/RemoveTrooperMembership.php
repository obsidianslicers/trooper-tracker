<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Command to remove a trooper's membership from a top-level organization.
 *
 * Deletes the trooper-organization record and clears member assignments
 * within that organization's hierarchy.
 *
 * @method static void call(Trooper $trooper, Organization $organization)
 */
final class RemoveTrooperMembership extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
    ) {}

    public function handle(): void
    {
        TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->organization->id)
            ->delete();

        $organization_ids = Organization::withinNodePath($this->organization->node_path)
            ->pluck(Organization::ID);

        if ($organization_ids->isNotEmpty())
        {
            $ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
                ->whereIn(TrooperAssignment::ORGANIZATION_ID, $organization_ids)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->pluck(TrooperAssignment::ID);

            if ($ids->isNotEmpty())
            {
                TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)
                    ->update([TrooperAssignment::IS_MEMBER => false]);
                TrooperAssignment::whereIn(TrooperAssignment::ID, $ids)->delete();
            }
        }
    }
}
