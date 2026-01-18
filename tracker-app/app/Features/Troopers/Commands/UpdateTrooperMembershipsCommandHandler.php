<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperAssignment;


readonly class UpdateTrooperCommandHandler
{
    public function __invoke(object $message): void
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
    }
}

