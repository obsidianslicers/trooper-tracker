<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\SignUpOutcome;
use App\Models\EventShift;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Signs a trooper up for a shift from the event page, themselves or a friend, when the event's
 * sign-up rules allow it. Command staff for the event may also add troopers during the grace period.
 *
 * The costume, backup costume and tentative choices are checked here: a costume that isn't one of
 * the trooper's is ignored, which leaves them on the shift needing a costume.
 *
 * @method static SignUpOutcome call(EventShift $event_shift, Trooper $trooper, Trooper $added_by_trooper, bool $is_for_another_trooper = false, ?int $organization_id = null, ?bool $is_handler = null, ?string $costume_id = null, ?string $backup_costume_id = null, ?int $event_shift_station_id = null, bool $is_tentative = false)
 */
final class SignUpEventTrooper extends Message
{
    /**
     * @param  bool  $is_for_another_trooper  The Add a Friend form named the trooper to sign up.
     * @param  bool|null  $is_handler  Null uses the trooper's own handler setting.
     * @param  string|null  $costume_id  A costume id, or 'none' for attending without a costume.
     * @param  bool  $is_tentative  They asked to sign up as Tentative.
     */
    public function __construct(
        private readonly EventShift $event_shift,
        private readonly Trooper $trooper,
        private readonly Trooper $added_by_trooper,
        private readonly bool $is_for_another_trooper = false,
        private readonly ?int $organization_id = null,
        private readonly ?bool $is_handler = null,
        private readonly ?string $costume_id = null,
        private readonly ?string $backup_costume_id = null,
        private readonly ?int $event_shift_station_id = null,
        private readonly bool $is_tentative = false,
    ) {}

    public function handle(): SignUpOutcome
    {
        if (!$this->isEligible())
        {
            return $this->hasReachedShiftLimit() ? SignUpOutcome::SHIFT_LIMIT_REACHED : SignUpOutcome::REFUSED;
        }

        $this->event_shift->loadMissing('event_shift_stations');

        if (!$this->event_shift->isValidStationChoice($this->event_shift_station_id))
        {
            return SignUpOutcome::STATION_REQUIRED;
        }

        $this->signUp();

        return SignUpOutcome::SIGNED_UP;
    }

    private function isEligible(): bool
    {
        $event = $this->event_shift->event;
        $can_moderate = $this->added_by_trooper->isModeratorForOrganization($event->organization);

        $can_add_friend = !$this->is_for_another_trooper
            || $can_moderate
            || $this->event_shift->canSignUpTrooper($this->added_by_trooper);

        $grace_period_moderator_add = $can_moderate
            && $event->is_within_grace_period
            && !$this->event_shift->isSignedUp($this->trooper);

        $can_sign_up = $this->event_shift->canSignUp(
            $this->trooper,
            require_own_mission_brief_ack: !$this->isFriendAdd(),
        );

        return $can_add_friend && ($can_sign_up || $grace_period_moderator_add);
    }

    private function signUp(): void
    {
        $costume_options = $this->event_shift->costumeOptionsFor($this->trooper, $this->organization_id);
        $is_attending_without_costume = $this->costume_id === 'none';

        CreateEventTrooper::call(
            event_shift: $this->event_shift,
            trooper: $this->trooper,
            added_by_trooper: $this->added_by_trooper,
            organization_id: $this->organization_id,
            is_handler: !$is_attending_without_costume && ($this->is_handler ?? (bool) $this->trooper->is_handler),
            costume_id: $this->ownCostumeId($this->costume_id, $costume_options),
            event_shift_station_id: $this->event_shift_station_id,
            is_attending_without_costume: $is_attending_without_costume,
            is_tentative: $this->isTentative(),
            backup_costume_id: $this->event_shift->event->requiresCostume()
                ? $this->ownCostumeId($this->backup_costume_id, $costume_options)
                : null,
        );
    }

    /**
     * @param  array<int, string>  $costume_options
     */
    private function ownCostumeId(?string $costume_id, array $costume_options): ?int
    {
        return is_numeric($costume_id) && array_key_exists((int) $costume_id, $costume_options)
            ? (int) $costume_id
            : null;
    }

    private function isTentative(): bool
    {
        return !$this->isFriendAdd()
            && $this->is_tentative
            && array_key_exists(
                EventTrooperStatus::TENTATIVE->value,
                $this->event_shift->event->planStatusOptions(),
            );
    }

    private function isFriendAdd(): bool
    {
        return $this->is_for_another_trooper && $this->trooper->id !== $this->added_by_trooper->id;
    }

    private function hasReachedShiftLimit(): bool
    {
        $event = $this->event_shift->event;

        return $event->shifts_allowed !== null
            && $event->getShiftCountFor($this->trooper) >= $event->shifts_allowed;
    }
}
