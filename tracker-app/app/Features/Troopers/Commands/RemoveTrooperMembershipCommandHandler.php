<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;

/**
 * Handler for removing a trooper's membership from a top-level organization.
 *
 * @implements CommandHandlerInterface<RemoveTrooperMembershipCommand>
 */
readonly class RemoveTrooperMembershipCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  RemoveTrooperMembershipCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $message->trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $message->organization->id)
            ->delete();

        $organization_ids = Organization::query()
            ->where(Organization::NODE_PATH, 'like', $message->organization->node_path.'%')
            ->pluck(Organization::ID);

        if ($organization_ids->isNotEmpty())
        {
            TrooperAssignment::query()
                ->where(TrooperAssignment::TROOPER_ID, $message->trooper->id)
                ->whereIn(TrooperAssignment::ORGANIZATION_ID, $organization_ids)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->update([TrooperAssignment::IS_MEMBER => false]);
        }

        return null;
    }
}
