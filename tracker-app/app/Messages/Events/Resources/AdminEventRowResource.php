<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff events list. Load it through `GetEventsForModerator`, which
 * counts the shifts and brings the viewer's moderator assignments.
 *
 * @mixin Event
 */
class AdminEventRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Event::ID => $this->id,
            Event::NAME => $this->name,
            'time_display' => $this->time_display,
            'shift_count' => (int) $this->event_shifts_count,
            'organization' => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ],
            'logo_url' => map_image_url(
                $this->organization->image_path_sm ?? '',
                'img/icons/organization-32x32.png'
            ),
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'can_update' => (bool) $request->user()?->can('update', $this->resource),
        ];
    }
}
