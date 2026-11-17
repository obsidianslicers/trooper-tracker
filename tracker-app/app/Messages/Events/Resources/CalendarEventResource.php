<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * An event on the calendar: its day, start time, host, and whether the trooper is in it.
 *
 * @mixin Event
 */
class CalendarEventResource extends JsonResource
{
    /**
     * @param  Collection<int, mixed>  $my_statuses  The trooper's sign-ups, by shift ID
     */
    public function __construct(Event $event, private readonly Collection $my_statuses)
    {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('events.display', ['event' => $this->resource]),
            'date' => $this->event_start->toDateString(),
            'starts' => $this->event_start->localFormat('g:ia'),
            'venue_address' => $this->venue_address,
            'organization_id' => $this->organization_id,
            'logo_url' => map_image_url(
                $this->organization?->image_path_sm,
                'img/icons/organization-32x32.png',
            ),
            'not_counted_notice' => $this->notCountedNotice(),
            'is_at_risk' => $this->at_risk,
            'is_mine' => $this->event_shifts->contains(
                fn (EventShift $shift) => $this->my_statuses->has($shift->id)
            ),
        ];
    }
}
