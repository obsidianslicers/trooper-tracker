<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\RosterAction;
use App\Enums\SignUpChangeOutcome;
use App\Jobs\SendEventRosterActivityNotificationsJob;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Puts a trooper who cancelled back on the shift, at the back of the line: going if there's room
 * (pending if they still need a costume), otherwise stand-by.
 *
 * @method static SignUpChangeOutcome call(EventTrooper $event_trooper, Trooper $signed_up_by_trooper)
 */
final class ReinstateEventTrooper extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $signed_up_by_trooper,
    ) {}

    public function handle(): SignUpChangeOutcome
    {
        $event_trooper = $this->event_trooper;

        if (!$event_trooper->canReinstate($this->signed_up_by_trooper))
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        $status = $this->isFull() ? EventTrooperStatus::STAND_BY : EventTrooperStatus::GOING;

        if ($status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
        {
            $status = EventTrooperStatus::PENDING;
        }

        UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: [
            EventTrooper::STATUS => $status,
            EventTrooper::SIGNED_UP_AT => now(),
        ]);

        dispatch(new SendEventRosterActivityNotificationsJob($event_trooper, RosterAction::RESIGNED_UP));

        return SignUpChangeOutcome::SAVED;
    }

    private function isFull(): bool
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;
        $is_handler = $event_trooper->is_handler;
        $effective_org_id = $event_trooper->organization_id
            ?? $event_trooper->effectiveOrgId($event_shift->event);

        $station_maxed = $event_trooper->event_shift_station_id !== null
            && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id);
        $global_maxed = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
        $org_maxed = $effective_org_id !== null && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);

        return $station_maxed || $global_maxed || $org_maxed;
    }
}
