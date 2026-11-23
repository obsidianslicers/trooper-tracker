<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Models\EventTrooper;
use App\Models\ModelChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One status change on the Trooper Status Change Log report (admin/reports/StatusChangeLog).
 *
 * @property ModelChange $resource
 */
class StatusChangeRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EventTrooper $event_trooper */
        $event_trooper = $this->resource->auditable;
        $event = $event_trooper->event_shift->event;

        return [
            ModelChange::ID => $this->resource->id,
            'changed_at' => $this->resource->created_at->localFormat('D - M d, Y g:ia'),
            'event_name' => $event->name,
            'event_url' => route('admin.events.edit', ['event' => $event]),
            'shift' => $event_trooper->event_shift->time_display,
            'trooper_name' => $event_trooper->trooper->display_name,
            'trooper_url' => route('admin.troopers.show', [
                'trooper' => $event_trooper->trooper,
                'tab' => 'changes',
            ]),
            'updated_by' => $this->resource->trooper->display_name ?? __('admin.System'),
        ];
    }
}
