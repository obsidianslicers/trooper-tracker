<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Commands;

use App\Models\EventType;
use Hyperdrive\Message;

/**
 * Adds an event type at the end of the list.
 *
 * @method static EventType call(string $name, ?string $description, bool $is_counted, bool $is_active)
 */
final class CreateEventType extends Message
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $is_counted,
        private readonly bool $is_active,
    ) {}

    public function handle(): EventType
    {
        $event_type = new EventType;
        $event_type->slug = EventType::uniqueSlugFor($this->name);
        $event_type->name = $this->name;
        $event_type->description = $this->description;
        $event_type->is_counted = $this->is_counted;
        $event_type->is_active = $this->is_active;
        $event_type->sort_order = (EventType::max(EventType::SORT_ORDER) ?? 0) + 1;

        $event_type->save();

        return $event_type;
    }
}
