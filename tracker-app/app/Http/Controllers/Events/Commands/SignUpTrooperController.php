<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
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
        $trooper = $request->user();

        $auth_trooper = $request->user();

        if ($request->has('trooper_id'))
        {
            //  auth_trooper is signing up on behalf of another trooper
            $trooper = Trooper::active()->findOrFail($request->input('trooper_id'));
        }

        $signed_up = false;

        $can_moderate = $auth_trooper->isModeratorForOrganization($event_shift->event->organization);

        $can_add_friend = !$request->has('trooper_id')
            || $can_moderate
            || $event_shift->canSignUpTrooper($auth_trooper);

        $grace_period_moderator_add = $can_moderate
            && $event_shift->event->is_within_grace_period
            && !$event_shift->isSignedUp($trooper);

        $is_friend_add = $request->has('trooper_id') && $trooper->id !== $auth_trooper->id;

        if ($can_add_friend && ($event_shift->canSignUp($trooper, require_own_mission_brief_ack: !$is_friend_add) || $grace_period_moderator_add))
        {
            $event_shift->loadMissing('event_shift_stations');

            $event_shift_station_id = $request->input('event_shift_station_id')
                ? (int) $request->input('event_shift_station_id')
                : null;

            if (!$event_shift->isValidStationChoice($event_shift_station_id))
            {
                return $this->backToEvent(
                    $event_shift->event,
                    __('events.Select a station before signing up.'),
                    FlashType::DANGER,
                );
            }

            $organization_id = $request->input('organization_id') ? (int) $request->input('organization_id') : null;

            $is_handler = (bool) $request->input('is_handler', $trooper->is_handler ? 1 : 0);

            //  the Sign Up form, and Add a Friend's, carry the costume of whoever is signing up (or
            //  "attending without a costume"). A costume that isn't one of theirs is ignored, which
            //  leaves them on the shift needing a costume, as before. Tentative is the trooper's
            //  own choice, where the event allows it.
            $costume_id = null;
            $is_attending_without_costume = false;
            $costume_options = $event_shift->costumeOptionsFor($trooper, $organization_id);

            $raw_costume_id = $request->input('costume_id');

            if ($raw_costume_id === 'none')
            {
                $is_attending_without_costume = true;
                $is_handler = false;
            }
            elseif (is_numeric($raw_costume_id) && array_key_exists((int) $raw_costume_id, $costume_options))
            {
                $costume_id = (int) $raw_costume_id;
            }

            //  the backup costume, optional, checked against their costumes the same way
            $raw_backup_costume_id = $request->input('backup_costume_id');
            $backup_costume_id = $event_shift->event->requiresCostume()
                && is_numeric($raw_backup_costume_id)
                && array_key_exists((int) $raw_backup_costume_id, $costume_options)
                ? (int) $raw_backup_costume_id
                : null;

            $is_tentative = !$is_friend_add
                && $request->input('status') === EventTrooperStatus::TENTATIVE->value
                && array_key_exists(EventTrooperStatus::TENTATIVE->value, $event_shift->event->planStatusOptions());

            SignUpEventTrooper::call(
                event_shift: $event_shift,
                trooper: $trooper,
                added_by_trooper: $auth_trooper,
                organization_id: $organization_id,
                is_handler: $is_handler,
                costume_id: $costume_id,
                event_shift_station_id: $event_shift_station_id,
                is_attending_without_costume: $is_attending_without_costume,
                is_tentative: $is_tentative,
                backup_costume_id: $backup_costume_id,
            );

            $signed_up = true;
        }

        return $this->answer($event_shift, $auth_trooper, $trooper, $signed_up);
    }

    /**
     * What the shift says right after a sign-up, depending on where it landed.
     */
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

    private function answer(EventShift $event_shift, Trooper $auth_trooper, Trooper $trooper, bool $signed_up): RedirectResponse
    {
        $event = $event_shift->event;

        if (!$signed_up)
        {
            $is_maxed = $event->shifts_allowed !== null
                && $event->getShiftCountFor($trooper) >= $event->shifts_allowed;

            return $this->backToEvent(
                $event,
                $is_maxed ? __('events.You have reached the maximum number of shift sign-ups allowed.') : null,
                FlashType::DANGER,
            );
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
