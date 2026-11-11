<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Commands;

use App\Models\EventType;
use Hyperdrive\Message;

/**
 * Removes an event type no event uses. The caller checks it is unused (the policy's
 * delete rule); the events table's foreign key refuses it otherwise.
 *
 * @method static void call(EventType $event_type)
 */
final class DeleteEventType extends Message
{
    public function __construct(
        private readonly EventType $event_type,
    ) {}

    public function handle(): void
    {
        $this->event_type->delete();
    }
}
