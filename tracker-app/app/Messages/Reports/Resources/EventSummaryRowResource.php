<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One closed event on the Event Summary report (admin/reports/EventSummary).
 *
 * @property Event $resource
 */
class EventSummaryRowResource extends JsonResource
{
    use FormatsReportNumbers;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Event::ID => $this->resource->id,
            Event::NAME => $this->resource->name,
            'url' => route('admin.events.edit', ['event' => $this->resource]),
            'logo_url' => map_image_url(
                $this->resource->organization?->image_path_sm,
                'img/icons/organization-32x32.png'
            ),
            'shifts' => (string) $this->resource->event_shifts_count,
            'unique_trooper_count' => $this->count($this->resource->unique_trooper_count),
            'total_trooper_count' => $this->count($this->resource->total_trooper_count),
            'direct' => $this->funds($this->resource->charity_direct_funds),
            'indirect' => $this->funds($this->resource->charity_indirect_funds),
        ];
    }
}
