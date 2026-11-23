<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Resources;

use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff event types list. Load the events count first.
 *
 * @mixin EventType
 */
class EventTypeRowResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            EventType::ID => $this->id,
            EventType::NAME => $this->name,
            EventType::DESCRIPTION => $this->description,
            EventType::IS_COUNTED => $this->is_counted,
            EventType::IS_COSTUME_REQUIRED => $this->is_costume_required,
            EventType::IS_ACTIVE => $this->is_active,
            'events_count' => $this->whenCounted('events'),
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}
