<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row on the completed or cancelled events lists: the event, who hosted it, and its totals.
 * Expects the counts GetEventSummary adds to each event.
 *
 * @mixin Event
 */
class EventSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('events.display', ['event' => $this->resource]),
            'date_label' => $this->event_start?->localFormat('M j, Y'),
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization?->name,
            'logo_url' => map_image_url(
                $this->organization?->image_path_sm,
                'img/icons/organization-32x32.png',
            ),
            'shifts' => (int) $this->event_shifts_count,
            'unique_troopers' => (int) ($this->unique_trooper_count ?? 0),
            'total_troopers' => (int) ($this->total_trooper_count ?? 0),
            'direct_funds' => (float) $this->charity_direct_funds,
            'indirect_funds' => (float) $this->charity_indirect_funds,
        ];
    }
}
