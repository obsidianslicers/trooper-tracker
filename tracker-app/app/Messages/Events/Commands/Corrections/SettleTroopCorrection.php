<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Enums\EventTrooperStatus;
use App\Enums\TroopCorrectionStatus;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\Events\TroopCorrectionDecidedNotification;
use Hyperdrive\Message;

/**
 * After a moderator approves or rejects a club on a sign-up: once no club is waiting, the
 * request for troop credit behind it is decided. Approved when any club gave the credit.
 * Denied otherwise, and the sign-up goes back to how it was before the request (or away,
 * when the request made it), so a turned-down request never reads as a No Show.
 *
 * Returns the request it decided, or null when there was none to decide yet, so plugins
 * can act on the outcome with afterMessage().
 *
 * @method static TroopCorrection|null call(EventTrooper $event_trooper, ?Trooper $moderator = null)
 */
final class SettleTroopCorrection extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly ?Trooper $moderator = null,
    ) {}

    public function handle(): ?TroopCorrection
    {
        $event_trooper = $this->event_trooper;
        $correction = $event_trooper->pending_troop_correction()->first();

        if ($correction === null || $event_trooper->isAwaitingVerification())
        {
            return null;
        }

        $approved = !empty($event_trooper->costume_organization_ids);

        if (!$approved)
        {
            $this->restoreSignUp($event_trooper, $correction->previous_status);
        }

        $correction->status = $approved ? TroopCorrectionStatus::APPROVED : TroopCorrectionStatus::DENIED;
        $correction->decided_by_trooper_id = $this->moderator?->id;
        $correction->decided_at = now();
        $correction->save();

        $correction->trooper->notify(new TroopCorrectionDecidedNotification($correction));

        return $correction;
    }

    private function restoreSignUp(EventTrooper $event_trooper, ?EventTrooperStatus $previous_status): void
    {
        if ($previous_status === null)
        {
            $event_trooper->delete();

            return;
        }

        $event_trooper->status = $previous_status;
        $event_trooper->save();
    }
}
