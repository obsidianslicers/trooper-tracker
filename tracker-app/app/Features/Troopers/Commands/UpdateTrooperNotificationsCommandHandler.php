<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperAssignment;


readonly class UpdateTrooperNotificationsCommandHandler
{
    public function __invoke(object $message): void
    {
        /** @var UpdateTrooperNotificationsCommand $message */
        $message->trooper->trooper_assignments()->update(['can_notify' => false]);

        $assignments = $message->trooper->trooper_assignments()->get();

        foreach ($message->valid_data as $organization_id => $data)
        {
            $assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $organization_id);

            if ($assignment === null)
            {
                $assignment = new TrooperAssignment();
                $assignment->trooper_id = $message->trooper->id;
                $assignment->organization_id = $organization_id;
            }

            $assignment->can_notify = $data['can_notify'] ?? false;

            $assignment->save();
        }
    }
}

