<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use Hyperdrive\Message;

/**
 * Command to update event details.
 *
 * Updates the event's core properties including name, status, attendance limits,
 * location coordinates, contact information, venue details, event timing,
 * request specifics, venue amenities, and miscellaneous fields.
 *
 * @method static void call(Event $event, array $data)
 */
final class UpdateEvent extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Event  $event  The event to update
     * @param  array<string, mixed>  $data  The validated data to update the event with
     */
    public function __construct(
        private readonly Event $event,
        private readonly array $data) {}

    /**
     * Execute the command to update the event.
     *
     * @return null
     */
    public function handle(): void
    {
        $this->event->fill($this->data);

        $this->event->save();
    }
}
