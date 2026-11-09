<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\PageData;

use App\Messages\EventTypes\Resources\EventTypeResource;
use App\Models\EventType;
use Hyperdrive\Message;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @method static array call()
 */
final class IndexPageData extends Message
{
    public function handle(): array
    {
        return [
            'event_types' => $this->getEventTypes(),
        ];
    }

    private function getEventTypes(): AnonymousResourceCollection
    {
        $event_types = EventType::query()->withCount('events')->ordered()->get();

        return EventTypeResource::collection($event_types);
    }
}
