<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\SignUpChangeOutcome;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\EventRosterCapacityService;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Changes the costume on a trooper's sign-up, or marks them attending without one.
 *
 * A costume decides whether they count as a trooper or a handler. Moving between the two frees a
 * spot in the old pool (the next in line moves up) and needs room in the new one. Picking a costume
 * settles a pending sign-up: going if there's room, otherwise stand-by.
 *
 * @method static SignUpChangeOutcome call(EventTrooper $event_trooper, ?string $costume_id, Trooper $changed_by_trooper)
 */
final class ChangeEventTrooperCostume extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  string|null  $costume_id  A costume id, 'none' for attending without a costume, or null to clear it.
     */
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly ?string $costume_id,
        private readonly Trooper $changed_by_trooper,
    ) {}

    public function handle(EventRosterCapacityService $capacity): SignUpChangeOutcome
    {
        $event_trooper = $this->event_trooper;

        if (!$event_trooper->canUpdateCostume($this->changed_by_trooper))
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        $attending_without_costume = $this->costume_id === 'none';
        $costume = $this->costume_id !== null && !$attending_without_costume
            ? Costume::find((int) $this->costume_id)
            : null;
        $is_handler = $costume?->countsAsHandler() ?? false;

        $previous_is_handler = $event_trooper->is_handler;
        $previous_status = $event_trooper->status;
        $was_going = $event_trooper->intendsToGo();
        //  before saving, so this trooper isn't counted in the new pool yet
        $new_pool_was_full = $this->isFull($is_handler);

        $org_ids = $costume !== null && !$costume->countsAsHandler()
            ? $costume->approvedOrgIdsForTrooper($event_trooper->trooper_id)
            : null;

        UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: [
            EventTrooper::COSTUME_ID => $attending_without_costume ? null : $this->costumeId(),
            EventTrooper::IS_HANDLER => $is_handler,
            EventTrooper::COSTUME_ORGANIZATION_IDS => !empty($org_ids) ? $org_ids : null,
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => $attending_without_costume,
        ]);

        if ($previous_is_handler !== $is_handler && $was_going)
        {
            $this->changePool($previous_is_handler, $new_pool_was_full);
        }

        $this->settleStatus($previous_status, $capacity);

        return SignUpChangeOutcome::SAVED;
    }

    private function costumeId(): ?int
    {
        return $this->costume_id !== null ? (int) $this->costume_id : null;
    }

    private function isFull(bool $is_handler): bool
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;
        $effective_org_id = $this->effectiveOrgId();

        $pool_full = $is_handler ? $event_shift->handlersMaxed() : $event_shift->troopersMaxed();
        $org_full = $effective_org_id !== null && $event_shift->orgTroopersMaxed($effective_org_id, $is_handler);
        $station_full = $event_trooper->event_shift_station_id !== null
            && $event_shift->stationMaxed($event_trooper->event_shift_station_id, $event_trooper->id);

        return $station_full || $pool_full || $org_full;
    }

    private function changePool(bool $previous_is_handler, bool $new_pool_was_full): void
    {
        //  they left their old pool: the next in that pool moves up
        PromoteNextInLineEventTrooper::call(
            event_trooper: $this->event_trooper,
            global_was_full: true,
            effective_org_id: $this->effectiveOrgId(),
            override_is_handler: $previous_is_handler,
        );

        if ($new_pool_was_full)
        {
            UpdateEventTrooper::call(
                event_trooper: $this->event_trooper,
                valid_data: [EventTrooper::STATUS => EventTrooperStatus::STAND_BY],
            );
        }
    }

    private function settleStatus(EventTrooperStatus $previous_status, EventRosterCapacityService $capacity): void
    {
        $event_trooper = $this->event_trooper;
        $event_shift = $event_trooper->event_shift;

        if ($previous_status === EventTrooperStatus::PENDING && !$event_trooper->needsCostumeBeforeGoing())
        {
            $can_go = $event_shift->event->status !== EventStatus::MANUAL_SELECTION && $capacity->canGo(
                $event_shift,
                $this->effectiveOrgId(),
                $event_trooper->event_shift_station_id,
                $event_trooper->is_handler,
            );

            $event_trooper->status = $can_go ? EventTrooperStatus::GOING : EventTrooperStatus::STAND_BY;
            $event_trooper->save();

            return;
        }

        if ($previous_status === EventTrooperStatus::GOING && $event_trooper->needsCostumeBeforeGoing())
        {
            $event_trooper->status = EventTrooperStatus::PENDING;
            $event_trooper->save();
        }
    }

    private function effectiveOrgId(): ?int
    {
        return $this->event_trooper->organization_id
            ?? $this->event_trooper->effectiveOrgId($this->event_trooper->event_shift->event);
    }
}
