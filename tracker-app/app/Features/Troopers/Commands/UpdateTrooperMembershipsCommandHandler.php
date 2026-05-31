<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\TrooperAssignment;

/**
 * Handler for updating trooper organization memberships.
 *
 * Creates or restores a TrooperAssignment record (is_member = true) for each
 * selected assignment organization, enabling the "Member Of" display on reload.
 *
 * @implements CommandHandlerInterface<UpdateTrooperMembershipsCommand>
 */
readonly class UpdateTrooperMembershipsCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  UpdateTrooperMembershipsCommand  $message
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        foreach ($message->valid_data as $organization_id => $data)
        {
            $assignment_id = $data['assignment'] ?? null;

            if (!$assignment_id)
            {
                continue;
            }

            $trooper_assignment = TrooperAssignment::withTrashed()
                ->where(TrooperAssignment::TROOPER_ID, $message->trooper->id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $assignment_id)
                ->first();

            if ($trooper_assignment)
            {
                if ($trooper_assignment->trashed())
                {
                    $trooper_assignment->restore();
                }

                $trooper_assignment->is_member = true;
                $trooper_assignment->save();

                continue;
            }

            TrooperAssignment::create([
                TrooperAssignment::TROOPER_ID => $message->trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $assignment_id,
                TrooperAssignment::IS_MEMBER => true,
            ]);
        }

        return null;
    }
}
