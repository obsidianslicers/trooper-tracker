<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One event type on the Event Type Counts report (admin/reports/EventTypeCount).
 *
 * @property object{event_type: EventType, count: int, unique_trooper_count: int, total_trooper_count: int} $resource
 */
class EventTypeCountRowResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event_type = $this->resource->event_type;

        return [
            EventType::ID => $event_type->id,
            EventType::NAME => $event_type->name,
            'events_url' => route('admin.events.index', ['event_type_id' => $event_type->id]),
            'count' => $this->count($this->resource->count),
            'unique_trooper_count' => $this->count($this->resource->unique_trooper_count),
            'total_trooper_count' => $this->count($this->resource->total_trooper_count),
        ];
    }
}
