<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A shift row on an upcoming event card: when, who is going, and whether the trooper is in it.
 *
 * @mixin EventShift
 */
class UpcomingEventShiftResource extends JsonResource
{
    public function __construct(
        EventShift $shift,
        private readonly Event $event,
        private readonly ?EventTrooperStatus $my_status,
    ) {
        parent::__construct($shift);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => route('events.display', ['event' => $this->event]).'#shift-'.$this->id,
            'date' => $this->shift_starts_at->localFormat('D M j'),
            'starts' => $this->shift_starts_at->localFormat('g:ia'),
            'ends' => $this->shift_ends_at->localFormat('g:ia'),
            'troopers' => (int) $this->event_troopers_count,
            'handlers' => (int) $this->event_handlers_count,
            'guests' => (int) $this->event_guests_count,
            'my_status' => $this->my_status?->value,
            'my_status_label' => $this->my_status?->label(),
        ];
    }
}
