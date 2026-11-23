<?php

declare(strict_types=1);

namespace App\Messages\EventTypes\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The event type on the Command Staff event type update page. Load the events count first: the
 * page warns that changing whether the type counts also changes those events.
 *
 * @mixin EventType
 */
class EventTypeResource extends JsonResource
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
            TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this),
        ];
    }
}
