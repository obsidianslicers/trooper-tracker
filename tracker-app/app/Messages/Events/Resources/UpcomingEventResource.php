<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * One card on the upcoming events list: what to show, plus what the filters match on.
 *
 * @mixin Event
 */
class UpcomingEventResource extends JsonResource
{
    /**
     * @param  Collection<int, EventTrooperStatus>  $my_statuses  The trooper's sign-ups, by shift ID
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
        $start = $this->event_start;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('events.display', ['event' => $this->resource]),
            'venue_address' => $this->venue_address,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization?->name,
            'logo_url' => map_image_url(
                $this->organization?->image_path_sm,
                'img/icons/organization-32x32.png',
            ),
            'month_key' => $start->format('Y-m'),
            'month_label' => $start->localFormat('F Y'),
            'weekday' => $start->localFormat('D'),
            'day' => $start->localFormat('j'),
            'month' => $start->localFormat('M'),
            'date_label' => $start->localFormat('D M d, Y'),
            'is_at_risk' => $this->at_risk,
            'is_locked' => $this->is_locked,
            'not_counted_notice' => $this->notCountedNotice(),
            'troopers_allowed' => $this->troopers_allowed,
            'shifts' => $this->event_shifts
                ->map(fn (EventShift $shift) => new UpcomingEventShiftResource(
                    $shift,
                    $this->resource,
                    $this->my_statuses->get($shift->id),
                ))
                ->values(),
            'organizations' => $this->organizations
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'can_attend' => (bool) ($organization->pivot->can_attend ?? false),
                ])
                ->values(),
        ];
    }
}
