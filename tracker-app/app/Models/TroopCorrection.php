<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\TroopCorrectionStatus;
use App\Models\Base\TroopCorrection as BaseTroopCorrection;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A trooper's request for credit for a troop at an event they never signed up for. The
 * claimed clubs wait on its event_troopers row (Awaiting Verification); this is the record
 * of who asked, what they said, who was asked to verify it and who decided.
 */
class TroopCorrection extends BaseTroopCorrection
{
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Days the event coordinator has before a reminder, and before moderators take it over.
     */
    public const REMIND_AFTER_DAYS = 7;

    public const ESCALATE_AFTER_DAYS = 14;

    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => TroopCorrectionStatus::class,
            self::PREVIOUS_STATUS => EventTrooperStatus::class,
        ]);
    }

    /**
     * The event asked about, even after it was deleted, so the trooper's list of requests
     * still names it.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    /**
     * The sign-up holding the claimed credit, even after a denial removed it.
     */
    public function event_trooper(): BelongsTo
    {
        return $this->belongsTo(EventTrooper::class)->withTrashed();
    }

    public function coordinator_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::COORDINATOR_TROOPER_ID);
    }

    public function decided_by_trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::DECIDED_BY_TROOPER_ID);
    }

    /**
     * Whether a trooper may ask for credit for the event: it is completed, they have no credit
     * or open request on it, and no sign-up they can still answer with "Made It".
     */
    public static function canBeRequestedFor(Event $event, Trooper $trooper): bool
    {
        if ($event->status !== EventStatus::CLOSED)
        {
            return false;
        }

        return $event->signUpsFor($trooper)->every(
            fn (EventTrooper $event_trooper): bool => self::replaces($event_trooper->status)
                && !$event_trooper->canMarkAttendance($trooper)
        );
    }

    /**
     * Whether a request may take over a sign-up with this status: anything but credit given or
     * waiting for verification.
     */
    public static function replaces(?EventTrooperStatus $status): bool
    {
        return !in_array($status, [EventTrooperStatus::ATTENDED, EventTrooperStatus::AWAITING_VERIFICATION], true);
    }

    public function isAwaitingCoordinator(): bool
    {
        return $this->status === TroopCorrectionStatus::AWAITING_COORDINATOR;
    }

    /**
     * Whether this trooper is the coordinator it is waiting for.
     */
    public function isAwaitingCoordinatorFrom(Trooper $trooper): bool
    {
        return $this->isAwaitingCoordinator() && $this->coordinator_trooper_id === $trooper->id;
    }

    /**
     * Who may look at the request: the trooper who asked, the coordinator asked to verify it,
     * and whoever may edit the trooper (command staff, their moderators), the same people
     * who see it on the trooper's service record.
     */
    public function isVisibleTo(Trooper $trooper): bool
    {
        return $trooper->id === $this->trooper_id
            || $trooper->id === $this->coordinator_trooper_id
            || $trooper->can('update', $this->trooper);
    }

    /**
     * @param  Builder<TroopCorrection>  $query
     * @return Builder<TroopCorrection>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn(self::STATUS, TroopCorrectionStatus::pending());
    }
}
