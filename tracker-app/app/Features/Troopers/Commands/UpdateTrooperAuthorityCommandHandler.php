<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipRole;
use App\Models\TrooperAssignment;

/**
 * Handler for updating a trooper's authority level and role.
 *
 * Updates trooper's membership_role (MEMBER, MODERATOR, ADMINISTRATOR)
 * and membership_status. Used by administrators to grant/revoke privileges.
 *
 * @implements CommandHandlerInterface<UpdateTrooperAuthorityCommand>
 */
readonly class UpdateTrooperAuthorityCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update trooper authority level.
     *
     * Sets the trooper's membership_role (MEMBER, MODERATOR, or ADMINISTRATOR)
     * and saves the trooper model.
     *
     * @param  UpdateTrooperAuthorityCommand  $message  The command with trooper and membership_role
     * @return null
     */
    public function __invoke(object $message): void
    {
        $message->trooper->membership_role = $message->membership_role;

        if ($message->membership_role === MembershipRole::VISITOR)
        {
            $message->trooper->visitor_expires_at = now()->addMonths(6);
            $message->trooper->visitor_notified_at = null;
        }
        else
        {
            $message->trooper->visitor_expires_at = null;
            $message->trooper->visitor_notified_at = null;
        }

        $message->trooper->save();

        //  reset all the moderatored organizations to "false"
        $message->trooper->trooper_assignments()
            ->update([TrooperAssignment::IS_MODERATOR => false]);

        if (!$message->trooper->is_moderator)
        {
            return;
        }

        foreach ($message->valid_data as $organization_id => $data)
        {
            $is_moderator = filter_var($data['is_moderator'], FILTER_VALIDATE_BOOLEAN) == true;

            if (!$is_moderator)
            {
                continue;
            }

            $where = [TrooperAssignment::ORGANIZATION_ID => $organization_id];

            $set = [TrooperAssignment::IS_MODERATOR => $is_moderator];

            $message->trooper->trooper_assignments()->updateOrCreate($where, $set);
        }

        return;
    }
}
