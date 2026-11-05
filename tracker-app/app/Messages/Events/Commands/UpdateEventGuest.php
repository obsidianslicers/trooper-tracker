<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventGuest;
use Hyperdrive\Message;

/**
 * Carries the data required to update an event guest's sign-up information.
 *
 * Encapsulates an EventGuest record and validated attributes, such as status
 * changes or name corrections, to be applied by UpdateEventGuestCommandHandler.
 *
 * @method static void call(EventGuest $event_guest, array $valid_data)
 */
final class UpdateEventGuest extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  EventGuest  $event_guest  The event guest record to update.
     * @param  array<string, mixed>  $valid_data  Validated attributes to update.
     */
    public function __construct(
        private readonly EventGuest $event_guest,
        private readonly array $valid_data,
    ) {}

    /**
     * Execute the command to update event guest information.
     *
     * @return null Always returns null.
     */
    public function handle(): void
    {
        $this->event_guest->fill($this->valid_data);

        $this->event_guest->save();
    }
}
