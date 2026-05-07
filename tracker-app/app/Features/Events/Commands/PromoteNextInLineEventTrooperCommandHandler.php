<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;

/**
 * Handler for promoting the next standby trooper to confirmed attendance.
 *
 * @implements CommandHandlerInterface<PromoteNextInLineEventTrooperCommand>
 */
readonly class PromoteNextInLineEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  PromoteNextInLineEventTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $event_trooper = $message->event_trooper;
        $event_shift = $event_trooper->event_shift;

        // Use the explicit org if set, otherwise use the effective org inferred
        // from the costume (passed by the caller or resolved here).
        $org_id = $message->effective_org_id
            ?? $event_trooper->organization_id
            ?? $event_trooper->effectiveOrgId($event_shift->event);

        $next_in_line = null;

        // Prefer the next STAND_BY trooper from the same organization when the
        // departing trooper held an org-limited slot. Also match troopers whose
        // costume org infers the same org (organization_id may be null).
        if ($org_id !== null)
        {
            $next_in_line = $event_shift->event_troopers()
                ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
                ->where(EventTrooper::IS_HANDLER, $event_trooper->is_handler)
                ->where(function ($q) use ($org_id) {
                    $q->where(EventTrooper::ORGANIZATION_ID, $org_id)
                        ->orWhereJsonContains(EventTrooper::COSTUME_ORGANIZATION_IDS, $org_id);
                })
                ->orderBy(EventTrooper::SIGNED_UP_AT)
                ->first();
        }

        // Fall back to any STAND_BY with the same role when the global event
        // capacity was also full, or when there was no org context at all.
        if ($next_in_line === null && ($message->global_was_full || $org_id === null))
        {
            $next_in_line = $event_shift->event_troopers()
                ->where(EventTrooper::STATUS, EventTrooperStatus::STAND_BY)
                ->where(EventTrooper::IS_HANDLER, $event_trooper->is_handler)
                ->orderBy(EventTrooper::SIGNED_UP_AT)
                ->first();
        }

        if ($next_in_line !== null)
        {
            $next_in_line->status = EventTrooperStatus::GOING;
            $next_in_line->save();

            $next_in_line->trooper->notify(new TrooperPromotedToGoingNotification($next_in_line));
        }

        return null;
    }
}
