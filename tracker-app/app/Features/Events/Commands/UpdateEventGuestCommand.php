<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventGuest;

/**
 * Carries the data required to update an event guest's sign-up information.
 *
 * Encapsulates an EventGuest record and validated attributes, such as status
 * changes or name corrections, to be applied by UpdateEventGuestCommandHandler.
 *
 * @see UpdateEventGuestCommandHandler
 */
readonly class UpdateEventGuestCommand
{
    /**
     * Create a new command instance.
     *
     * @param  EventGuest  $event_guest  The event guest record to update.
     * @param  array<string, mixed>  $valid_data  Validated attributes to update.
     */
    public function __construct(
        public EventGuest $event_guest,
        public array $valid_data,
    ) {
    }
}
