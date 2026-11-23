<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The event type picker on Create Event and the event's Overview tab, with the line that
 * explains the chosen type and whether it counts toward troop totals.
 */
class EventTypeOptionCollection extends ResourceCollection
{
    /**
     * @return array<int, array{value: int, label: string, description: string, is_counted: bool}>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (EventType $type): array => [
                'value' => $type->id,
                'label' => $type->name,
                EventType::DESCRIPTION => (string) $type->description,
                EventType::IS_COUNTED => (bool) $type->is_counted,
            ])
            ->values()
            ->all();
    }
}
