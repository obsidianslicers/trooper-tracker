<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\TrooperAssignment;

/**
 * Handler for updating trooper organization memberships.
 *
 * Creates or updates TrooperAssignment records based on the provided
 * assignment data. Each entry in valid_data may contain an 'assignment'
 * key specifying the organization ID where the trooper is a member.
 *
 * Only processes entries that have a valid assignment ID.
 *
 * @implements CommandHandlerInterface<UpdateTrooperMembershipsCommand>
 */
readonly class UpdateTrooperMembershipsCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update trooper organization memberships.
     *
     * Syncs TrooperAssignment records (is_member flags) for each organization
     * based on the valid_data array. Creates, updates, or deletes assignments
     * as needed to match the submitted form data.
     *
     * @param  UpdateTrooperMembershipsCommand  $message  The command with trooper and membership assignment data
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

            $trooper_assignment = $message->trooper->trooper_assignments
                ->firstWhere('organization_id', $assignment_id);

            if (!$trooper_assignment)
            {
                $trooper_assignment = new TrooperAssignment;
                $trooper_assignment->trooper_id = $message->trooper->id;
                $trooper_assignment->organization_id = $assignment_id;
            }

            $trooper_assignment->is_member = true;
            $trooper_assignment->save();
        }

        return null;
    }
}
