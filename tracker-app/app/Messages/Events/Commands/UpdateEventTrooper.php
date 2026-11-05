<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * Command to update an event trooper's sign-up information.
 *
 * Updates an existing EventTrooper record with validated data,
 * such as status changes, costume selections, or other attributes.
 *
 * @method static void call(EventTrooper $event_trooper, array $valid_data)
 */
final class UpdateEventTrooper extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  EventTrooper  $event_trooper  The event trooper record to update.
     * @param  array<string, mixed>  $valid_data  Validated attributes to update.
     */
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly array $valid_data,
    ) {}

    /**
     * Execute the command to update event trooper information.
     *
     * @return null Always returns null.
     */
    public function handle(): void
    {
        $this->event_trooper->fill($this->valid_data);

        $this->event_trooper->save();
    }
}
