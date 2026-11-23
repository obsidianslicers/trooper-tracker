<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
use App\Enums\SignUpOutcome;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\SignUpEventTrooper;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Signs a trooper (or a friend they bring) up for a shift from the event page.
 *
 * This controller processes a trooper's sign-up for a specific event shift.
 * It automatically determines if the trooper should be placed on the main roster
 * or the standby list based on shift capacity, and goes back to the event page with a toast
 * saying where the sign-up landed.
 */
class SignUpTrooperController extends PageController
{
    use AnswersWithEventPage;

    /**
     * Handle the incoming request to sign up a trooper for an event shift
     *
     * Creates a new EventTrooper record for the specified or authenticated trooper,
     * automatically determining their status (GOING or STAND_BY) based on the shift's
     * capacity limits. Supports moderators signing up other troopers via trooper_id
     * parameter.
     *
     * @param  Request  $request  The incoming request (may contain optional trooper_id for moderator signups)
     * @param  EventShift  $event_shift  The event shift the trooper is signing up for
     */
    public function __invoke(Request $request, EventShift $event_shift): RedirectResponse
    {
        $auth_trooper = $request->user();

        //  with trooper_id, they're signing up someone else (Add a Friend, or command staff)
        $trooper = $request->has('trooper_id')
            ? Trooper::active()->findOrFail($request->input('trooper_id'))
            : $auth_trooper;

        $outcome = SignUpEventTrooper::call(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $auth_trooper,
            is_for_another_trooper: $request->has('trooper_id'),
            organization_id: $this->optionalId($request->input('organization_id')),
            is_handler: $request->has('is_handler') ? (bool) $request->input('is_handler') : null,
            costume_id: $this->optionalString($request->input('costume_id')),
            backup_costume_id: $this->optionalString($request->input('backup_costume_id')),
            event_shift_station_id: $this->optionalId($request->input('event_shift_station_id')),
            is_tentative: $request->input('status') === EventTrooperStatus::TENTATIVE->value,
        );

        return $this->answer($event_shift, $auth_trooper, $trooper, $outcome);
    }

    private function optionalId(mixed $value): ?int
    {
        return $value ? (int) $value : null;
    }

    private function optionalString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private function signedUpMessage(EventShift $event_shift, Trooper $trooper, bool $is_self): string
    {
        $status = $event_shift->event_troopers->firstWhere(EventTrooper::TROOPER_ID, $trooper->id)?->status;
        $name = $trooper->display_name;

        return match (true)
        {
            $status === EventTrooperStatus::STAND_BY && $event_shift->event->status === EventStatus::MANUAL_SELECTION => $is_self
                ? __("events.You're signed up. Command staff picks who goes to this event; you're on the stand-by list until then.")
                : __('events.:name is signed up and on the stand-by list until command staff picks who goes.', ['name' => $name]),
            $status === EventTrooperStatus::STAND_BY => $is_self
                ? __("events.This shift is full, so you're on the stand-by list. You'll move up if a spot opens.")
                : __('events.This shift is full, so :name is on the stand-by list.', ['name' => $name]),
            $status === EventTrooperStatus::PENDING => $is_self
                ? __("events.You're on the shift.").' '.__('events.Select a costume, or mark attending without one, to confirm your spot.')
                : __('events.:name is on the shift. Pick their costume on their row to confirm the spot.', ['name' => $name]),
            default => $is_self
                ? __("events.You're signed up.")
                : __('events.:name is signed up.', ['name' => $name]),
        };
    }

    private function answer(
        EventShift $event_shift,
        Trooper $auth_trooper,
        Trooper $trooper,
        SignUpOutcome $outcome,
    ): RedirectResponse {
        $event = $event_shift->event;

        if ($outcome !== SignUpOutcome::SIGNED_UP)
        {
            return $this->backToEvent($event, match ($outcome)
            {
                SignUpOutcome::STATION_REQUIRED => __('events.Select a station before signing up.'),
                SignUpOutcome::SHIFT_LIMIT_REACHED => __('events.You have reached the maximum number of shift sign-ups allowed.'),
                default => null,
            }, FlashType::DANGER);
        }

        $same_day_shifts = $trooper->id === $auth_trooper->id
            ? $event->sameDayShiftsFor($event_shift, $trooper)
            : collect();

        if ($same_day_shifts->isNotEmpty())
        {
            //  "one troop per day": said again the moment they sign up for a second shift that day
            return $this->backToEvent($event, __(
                "events.You're signed up. This troop counts as one troop for the day. You're currently signed up for :count shifts that day.",
                ['count' => $same_day_shifts->count() + 1]
            ), FlashType::INFO);
        }

        $event_shift->load('event_troopers');

        return $this->backToEvent($event, $this->signedUpMessage($event_shift, $trooper, $trooper->id === $auth_trooper->id));
    }
}
