<?php

declare(strict_types=1);

namespace App\Messages\Search\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An event found by the search box (search/Results).
 *
 * @mixin Event
 */
class SearchEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Event::ID => $this->resource->id,
            Event::NAME => $this->resource->name,
            'url' => route('events.display', ['event' => $this->resource]),
            Event::VENUE_ADDRESS => $this->resource->venue_address ?: null,
            'organization' => $this->resource->organization?->name,
            'date' => $this->resource->event_start?->localFormat('M j, Y'),
            'status' => $this->resource->status->label(),
        ];
    }
}
