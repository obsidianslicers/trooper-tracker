<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Commands;

use App\Models\EventType;
use Hyperdrive\Message;

/**
 * Saves an event type's settings. Events point at it by id, so they keep it through a rename.
 *
 * @method static EventType call(EventType $event_type, string $name, ?string $description, bool $is_counted, bool $is_active)
 */
final class UpdateEventType extends Message
{
    public function __construct(
        private readonly EventType $event_type,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $is_counted,
        private readonly bool $is_active,
    ) {}

    public function handle(): EventType
    {
        $this->event_type->name = $this->name;
        $this->event_type->description = $this->description;
        $this->event_type->is_counted = $this->is_counted;
        $this->event_type->is_active = $this->is_active;

        $this->event_type->save();

        return $this->event_type;
    }
}
