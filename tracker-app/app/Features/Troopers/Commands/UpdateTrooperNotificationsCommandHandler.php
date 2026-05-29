<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\TrooperAssignment;

/**
 * Handler for updating trooper notification preferences.
 *
 * Process:
 * 1. Resets all existing assignments to should_notify = false
 * 2. Updates or creates assignments for each provided organization
 * 3. Sets should_notify flag based on the provided data
 *
 * This ensures notification preferences are completely replaced,
 * not incrementally updated.
 *
 * @implements CommandHandlerInterface<UpdateTrooperNotificationsCommand>
 */
readonly class UpdateTrooperNotificationsCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update notification preferences.
     *
     * @param  UpdateTrooperNotificationsCommand  $message  The command with trooper and preference data
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        $message->trooper->trooper_assignments()->update(['should_notify' => false]);

        $assignments = $message->trooper->trooper_assignments()->get();

        foreach ($message->valid_data as $organization_id => $data)
        {
            $assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, $organization_id);
            $should_notify = $data['should_notify'] ?? false;

            if ($assignment === null)
            {
                $assignment = new TrooperAssignment;
                $assignment->trooper_id = $message->trooper->id;
                $assignment->organization_id = $organization_id;
                $assignment->should_notify = $should_notify;
                $assignment->save();
            }
            else
            {
                $message->trooper->trooper_assignments()
                    ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
                    ->update(['should_notify' => $should_notify]);
            }
        }

        return null;
    }
}
