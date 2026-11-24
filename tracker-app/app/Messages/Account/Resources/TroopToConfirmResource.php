<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A troop waiting for "did you make it?" at the top of the notifications inbox
 * (account/PushNotifications).
 *
 * @mixin EventTrooper
 */
class TroopToConfirmResource extends JsonResource
{
    /**
     * @return array{id: int, event_name: string, shift_time: string, url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_name' => $this->event_shift->event->name,
            'shift_time' => $this->event_shift->time_display,
            'url' => route('events.display', ['event' => $this->event_shift->event_id])
                .'#shift-'.$this->event_shift_id,
        ];
    }
}
