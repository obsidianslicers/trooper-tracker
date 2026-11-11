<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\PageData;

use App\Messages\EventTypes\Resources\EventTypeResource;
use App\Models\EventType;
use Hyperdrive\Message;

/**
 * @method static array call(EventType $event_type)
 */
final class UpdatePageData extends Message
{
    public function __construct(
        private readonly EventType $event_type,
    ) {}

    public function handle(): array
    {
        $this->event_type->loadCount('events');

        return [
            'event_type' => new EventTypeResource($this->event_type),
        ];
    }
}
