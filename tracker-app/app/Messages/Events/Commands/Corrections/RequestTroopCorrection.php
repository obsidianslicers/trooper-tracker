<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Enums\EventStatus;
use App\Enums\TroopCorrectionStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\Events\TroopCorrectionRequestedNotification;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use RuntimeException;

/**
 * A trooper asks for credit for a completed event they never signed up for. Their sign-up
 * on the shift waits in Awaiting Verification, like a flagged trooper's troop, and the
 * request is recorded with the details they gave.
 *
 * The event coordinator is asked first. Moderators get it straight away when the event has
 * no coordinator, the trooper coordinated it, or the trooper's troops always need a
 * moderator (flagged as do not trust).
 *
 * Independent of the HTTP request, so plugins can call it too. The caller checks the
 * trooper may ask (see `TroopCorrection::canBeRequestedFor`).
 *
 * @method static TroopCorrection call(Trooper $trooper, Event $event, string $details, array $organization_ids = [], ?EventShift $event_shift = null, ?int $costume_id = null)
 */
final class RequestTroopCorrection extends Message
{
    use ShouldBeTransactional;

    /**
     * @param  array<int, int>  $organization_ids  The top-level clubs to credit; none means every
     *                                             club the costume can credit.
     * @param  EventShift|null  $event_shift  The shift they trooped; the event's first by default.
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Event $event,
        private readonly string $details,
        private readonly array $organization_ids = [],
        private readonly ?EventShift $event_shift = null,
        private readonly ?int $costume_id = null,
    ) {}

    public function handle(): TroopCorrection
    {
        $shift = $this->shift();
        $event_trooper = $this->findOrNewEventTrooper($shift);
        $previous_status = $event_trooper->exists && !$event_trooper->trashed()
            ? $event_trooper->status
            : null;

        $this->fill($event_trooper, $shift);
        $event_trooper->requestVerification($this->organizationIds($event_trooper));

        if ($event_trooper->trashed())
        {
            $event_trooper->restore();
        }
        else
        {
            $event_trooper->save();
        }

        $correction = TroopCorrection::create([
            TroopCorrection::TROOPER_ID => $this->trooper->id,
            TroopCorrection::EVENT_ID => $this->event->id,
            TroopCorrection::EVENT_TROOPER_ID => $event_trooper->id,
            TroopCorrection::PREVIOUS_STATUS => $previous_status,
            TroopCorrection::DETAILS => trim($this->details),
            TroopCorrection::STATUS => TroopCorrectionStatus::AWAITING_COORDINATOR,
        ]);

        $this->askVerifier($correction);

        return $correction;
    }

    private function shift(): EventShift
    {
        if ($this->event->status !== EventStatus::CLOSED)
        {
            throw new RuntimeException('Troop credit can only be requested for completed events.');
        }

        $shift = $this->event_shift ?? $this->event->event_shifts()->orderBy(EventShift::SHIFT_STARTS_AT)->first();

        if ($shift === null || $shift->event_id !== $this->event->id)
        {
            throw new RuntimeException('That shift is not part of this event.');
        }

        return $shift;
    }

    /**
     * The (event_shift_id, trooper_id) unique index ignores soft deletes, so an earlier sign-up
     * on the shift is reused: one they cancelled, missed or were removed from.
     */
    private function findOrNewEventTrooper(EventShift $shift): EventTrooper
    {
        $existing = EventTrooper::withTrashed()
            ->where(EventTrooper::EVENT_SHIFT_ID, $shift->id)
            ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
            ->lockForUpdate()
            ->first();

        if ($existing !== null && !$existing->trashed() && !TroopCorrection::replaces($existing->status))
        {
            throw new RuntimeException('The trooper already has credit or a request on this shift.');
        }

        return $existing ?? new EventTrooper;
    }

    private function fill(EventTrooper $event_trooper, EventShift $shift): void
    {
        $event_trooper->event_shift_id = $shift->id;
        $event_trooper->trooper_id = $this->trooper->id;
        $event_trooper->setRelation('trooper', $this->trooper);
        $event_trooper->costume_id = $this->costume_id;
        $event_trooper->unsetRelation('costume');
        $event_trooper->is_attending_without_costume = $this->costume_id === null;
        $event_trooper->is_handler = false;
        $event_trooper->added_by_trooper_id = null;
        $event_trooper->signed_up_at ??= now();
    }

    /**
     * @return array<int, int>
     */
    private function organizationIds(EventTrooper $event_trooper): array
    {
        $organization_ids = $this->organization_ids === []
            ? $event_trooper->getEligibleCreditOrganizations()->pluck('id')->all()
            : $event_trooper->childOrgIdsForSelectedParents(array_map('intval', $this->organization_ids));

        return $organization_ids === [] ? [$this->event->organization_id] : $organization_ids;
    }

    private function askVerifier(TroopCorrection $correction): void
    {
        $coordinator = $this->event->eventCoordinator();

        if ($coordinator === null
            || !$coordinator->is_active
            || $coordinator->id === $this->trooper->id
            || $this->trooper->is_untrusted)
        {
            EscalateTroopCorrection::call(troop_correction: $correction);

            return;
        }

        $correction->coordinator_trooper_id = $coordinator->id;
        $correction->save();

        $coordinator->notify(new TroopCorrectionRequestedNotification($correction));
    }
}
