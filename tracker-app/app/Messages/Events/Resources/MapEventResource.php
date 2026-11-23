<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An event as a pin on the map: where it is, when, and who hosts it (for the squad filter).
 *
 * @mixin Event
 */
class MapEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'url' => route('events.display', ['event' => $this->resource]),
            'date_range' => $this->time_display,
            'venue_address' => $this->venue_address,
            'organization_id' => $this->organization_id,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}
