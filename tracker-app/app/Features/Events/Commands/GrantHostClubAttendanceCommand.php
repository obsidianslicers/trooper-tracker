<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\Event;

/**
 * Command to grant the event's hosting organization primary club attendance.
 *
 * Ensures the costume club at the root of the event's hosting organization has
 * an EventOrganization record permitting sign-ups. Used on event creation and
 * whenever the hosting organization is reassigned.
 *
 * @see GrantHostClubAttendanceCommandHandler
 */
readonly class GrantHostClubAttendanceCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event whose hosting club should be granted attendance
     */
    public function __construct(
        public Event $event) {}
}
