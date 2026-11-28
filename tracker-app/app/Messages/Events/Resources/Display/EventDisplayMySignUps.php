<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * "Your sign-up" near the top of the event page: where the viewer stands on each shift, and
 * whether there's a shift left to sign up for, and, on a completed event they have no credit
 * for, where to ask for it.
 */
class EventDisplayMySignUps extends JsonResource
{
    public function __construct(private readonly EventDisplayContext $context)
    {
        parent::__construct($context->event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->context->event;
        $multiple_shifts = $event->event_shifts->count() > 1;

        return [
            'has_open_shift' => $event->event_shifts->contains(fn (EventShift $s) => $s->is_open),
            'open_shift_ids' => $event->event_shifts
                ->filter(fn (EventShift $s) => $s->is_open)
                ->pluck(EventShift::ID)
                ->values()
                ->all(),
            'sign_ups' => $event->signUpsFor($this->context->viewer)
                ->map(fn (EventTrooper $et) => [
                    'id' => $et->id,
                    'shift_id' => $et->event_shift_id,
                    'time' => $multiple_shifts ? $et->event_shift->short_time_display : null,
                    'status' => EventDisplayStatus::of($et->status),
                    'is_active' => $et->status !== EventTrooperStatus::CANCELLED,
                    'costume' => $event->requiresCostume() ? $et->costume?->name : null,
                    'is_without_costume' => (bool) $et->is_attending_without_costume,
                    'is_handler' => (bool) $et->is_handler,
                    'requires_costume' => $event->requiresCostume(),
                    'backup' => $et->backup_costume?->name,
                    'can_change' => $et->event_shift->is_open,
                ])
                ->values()
                ->all(),
            'request_credit_url' => TroopCorrection::canBeRequestedFor($event, $this->context->viewer)
                ? route('events.troop-correction-form', ['event_id' => $event->id])
                : null,
        ];
    }
}
