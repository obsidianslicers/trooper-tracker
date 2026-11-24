<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Support\OneTroopPerDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One of a trooper's shifts on their service record (service-records/Trooper): an upcoming
 * one, a past one (History) or one waiting for "did you make it?". The kind picks the fields.
 *
 * @property EventShift&object{event_trooper: EventTrooper} $resource
 */
class ServiceRecordShiftResource extends JsonResource
{
    public const UPCOMING = 'upcoming';

    public const RECENT = 'recent';

    public const PENDING = 'pending';

    public function __construct(EventShift $shift, private readonly string $kind)
    {
        parent::__construct($shift);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shift = $this->resource;

        return [
            'id' => $shift->id,
            'event_name' => $shift->event->name,
            'event_url' => route('events.display', ['event' => $shift->event]),
            'date' => $shift->full_date_display,
            'time' => $shift->compact_time_display,
            ...match ($this->kind)
            {
                self::UPCOMING => $this->upcoming(),
                self::RECENT => $this->recent(),
                default => $this->pending(),
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function upcoming(): array
    {
        $event_trooper = $this->resource->event_trooper;

        return [
            'logo_url' => $this->logoUrl(),
            'costume' => $event_trooper->costume?->name,
            'costume_organizations' => $event_trooper->costume ? $event_trooper->costume_organizations : null,
            'is_handler' => (bool) $event_trooper->is_handler,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recent(): array
    {
        $event_trooper = $this->resource->event_trooper;
        $event = $this->resource->event;

        return [
            'logo_url' => $this->logoUrl(),
            'costume' => $event_trooper->costume?->name,
            'is_handler' => (bool) $event_trooper->is_handler,
            'credited_to' => $event_trooper->attended ? array_values($event_trooper->credited_org_names ?? []) : [],
            'not_counted' => $event->isCounted() ? null : [
                'label' => EventType::NOT_COUNTED_LABEL,
                'hint' => $event->notCountedNotice(),
            ],
            'same_day_repeat' => $event_trooper->is_same_day_repeat ? [
                'label' => __('events.'.OneTroopPerDay::REPEAT_LABEL),
                'hint' => __('events.'.OneTroopPerDay::REPEAT_HINT),
            ] : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pending(): array
    {
        $event_trooper = $this->resource->event_trooper;

        return [
            'made_it_url' => $event_trooper->getAttendanceUrl(EventTrooperStatus::ATTENDED),
            'missed_it_url' => $event_trooper->getAttendanceUrl(EventTrooperStatus::UNABLE_TO_ATTEND),
        ];
    }

    private function logoUrl(): string
    {
        return map_image_url(
            $this->resource->event->organization->image_path_sm ?? '',
            'img/icons/organization-32x32.png'
        );
    }
}
