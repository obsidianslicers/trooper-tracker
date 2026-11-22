<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A troop waiting for "did you make it?": the event, the shift, and where the answer goes.
 * Expects the shift and event GetTroopsToConfirm loads.
 *
 * @mixin EventTrooper
 */
class TroopToConfirmResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shift = $this->event_shift;

        return [
            'id' => $this->id,
            'event_name' => $shift->event->name,
            'event_url' => route('events.display', ['event' => $shift->event_id]).'#shift-'.$shift->id,
            'shift_label' => $shift->time_display,
            'is_tentative' => $this->status === EventTrooperStatus::TENTATIVE,
            'confirm_url' => route('events.confirm-attendance', ['event_trooper' => $this->id]),
        ];
    }
}
