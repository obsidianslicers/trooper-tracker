<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;

/**
 * Command to update a trooper's authority level within organizations.
 *
 * Updates TrooperAssignment records to set a trooper's membership_role
 * (MEMBER, MODERATOR, or ADMINISTRATOR) for specified organizations.
 * This controls what permissions the trooper has within each organization.
 *
 * @method static void call(Trooper $trooper, string|MembershipRole $membership_role, array $valid_data)
 */
final class UpdateTrooperAuthority extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper whose authority to update
     * @param  string|MembershipRole  $membership_role  The role to assign (MEMBER, MODERATOR, or ADMINISTRATOR)
     * @param  array<int, array{is_moderator: bool|null}>  $valid_data  Organization IDs mapped to assignment data
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly string|MembershipRole $membership_role,
        private readonly array $valid_data,
    ) {}

    /**
     * Execute the command to update trooper authority level.
     *
     * Sets the trooper's membership_role (MEMBER, MODERATOR, or ADMINISTRATOR)
     * and saves the trooper model.
     *
     * @return null
     */
    public function handle(): void
    {
        $this->trooper->membership_role = $this->membership_role;

        if ($this->membership_role === MembershipRole::VISITOR)
        {
            $this->trooper->visitor_expires_at = now()->addMonths(6);
            $this->trooper->visitor_notified_at = null;
        }
        else
        {
            $this->trooper->visitor_expires_at = null;
            $this->trooper->visitor_notified_at = null;
        }

        $this->trooper->save();

        //  reset all the moderatored organizations to "false"
        $this->trooper->trooper_assignments()
            ->update([TrooperAssignment::IS_MODERATOR => false]);

        $assignments = $this->trooper->trooper_assignments()->withTrashed()->get();

        if (!$this->trooper->is_moderator)
        {
            return;
        }

        foreach ($this->valid_data as $organization_id => $data)
        {
            $is_moderator = filter_var($data['is_moderator'], FILTER_VALIDATE_BOOLEAN) == true;

            if (!$is_moderator)
            {
                continue;
            }

            $assignment = $assignments->firstWhere(TrooperAssignment::ORGANIZATION_ID, (int) $organization_id);

            if ($assignment === null)
            {
                $assignment = new TrooperAssignment;
                $assignment->{TrooperAssignment::TROOPER_ID} = $this->trooper->id;
                $assignment->{TrooperAssignment::ORGANIZATION_ID} = $organization_id;
            }
            elseif ($assignment->trashed())
            {
                $assignment->restore();
            }

            $assignment->{TrooperAssignment::IS_MODERATOR} = $is_moderator;
            $assignment->save();
        }
    }
}
