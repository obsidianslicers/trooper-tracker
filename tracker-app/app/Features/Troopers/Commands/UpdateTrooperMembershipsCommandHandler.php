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
     * Execute the command to update memberships.
     *
     * @param UpdateTrooperMembershipsCommand $message The command with trooper and membership data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        /** @var UpdateTrooperMembershipsCommand $message */
        foreach ($message->valid_data as $organization_id => $data)
        {
            $assignmentId = $data['assignment'] ?? null;

            if (!$assignmentId)
            {
                continue;
            }

            $assignment = $message->trooper->trooper_assignments
                ->firstWhere('organization_id', $assignmentId);

            if (!$assignment)
            {
                $assignment = new TrooperAssignment();
                $assignment->trooper_id = $message->trooper->id;
                $assignment->organization_id = $assignmentId;
            }

            $assignment->is_member = true;
            $assignment->save();
        }

        return null;
    }
}

