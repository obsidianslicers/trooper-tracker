<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\ShiftAttendanceOutcome;
use App\Messages\Events\Commands\Verification\RequestTroopCredit;
use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * Records a trooper's answer to "did you make it?" for a closed shift: Attended or Unable to
 * Attend. Every way of answering goes through here: the email links, the bell, the "Troops to
 * confirm" page and the club choice.
 *
 * - A troop already sent for verification is left as it is.
 * - Attended with a costume that can credit more than one club asks for the club first, unless
 *   the clubs were chosen ($parent_organization_ids).
 * - Attended from a trooper whose troops need verification is sent to a moderator, not recorded.
 *
 * Who may answer is the caller's check.
 *
 * @method static ShiftAttendanceOutcome call(EventTrooper $event_trooper, EventTrooperStatus $status, ?array $parent_organization_ids = null)
 */
final class ConfirmShiftAttendance extends Message
{
    /**
     * @param  array<int, int>|null  $parent_organization_ids  The top-level clubs picked on the club choice page.
     */
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly EventTrooperStatus $status,
        private readonly ?array $parent_organization_ids = null,
    ) {}

    public function handle(): ShiftAttendanceOutcome
    {
        $event_trooper = $this->event_trooper;
        $chose_clubs = $this->parent_organization_ids !== null;

        if (!$chose_clubs && $event_trooper->isAwaitingVerification())
        {
            return ShiftAttendanceOutcome::AWAITING_VERIFICATION;
        }

        if (!$event_trooper->event_shift->event->can_update_trooper_status)
        {
            return ShiftAttendanceOutcome::CLOSED;
        }

        $valid_data = [
            EventTrooper::STATUS => $this->status,
        ];

        if ($this->status === EventTrooperStatus::ATTENDED)
        {
            if ($chose_clubs)
            {
                // organization_id is intentionally not set: multi-club credit flows via costume_organization_ids
                $valid_data[EventTrooper::COSTUME_ORGANIZATION_IDS] = $event_trooper->childOrgIdsForSelectedParents($this->parent_organization_ids);
            }
            else
            {
                if ($event_trooper->getEligibleCreditParentOrganizations()->count() > 1)
                {
                    return ShiftAttendanceOutcome::CHOOSE_CLUB;
                }

                if (empty($event_trooper->costume_organization_ids))
                {
                    $eligible_orgs = $event_trooper->getEligibleCreditOrganizations();

                    if ($eligible_orgs->isNotEmpty())
                    {
                        $valid_data[EventTrooper::COSTUME_ORGANIZATION_IDS] = $eligible_orgs->pluck('id')->values()->all();
                    }
                }
            }

            if ($event_trooper->isAwaitingVerification() || $event_trooper->trooper->is_untrusted)
            {
                if (!$event_trooper->isAwaitingVerification())
                {
                    $this->requestVerification($valid_data);
                }

                return ShiftAttendanceOutcome::AWAITING_VERIFICATION;
            }
        }

        UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: $valid_data);

        return ShiftAttendanceOutcome::RECORDED;
    }

    /**
     * @param  array<string, mixed>  $valid_data
     */
    private function requestVerification(array $valid_data): void
    {
        $organization_ids = $valid_data[EventTrooper::COSTUME_ORGANIZATION_IDS]
            ?? $this->event_trooper->creditedOrgIds();

        if (empty($organization_ids))
        {
            $organization_ids = [$this->event_trooper->event_shift->event->organization_id];
        }

        RequestTroopCredit::call(event_trooper: $this->event_trooper, organization_ids: $organization_ids);
    }
}
