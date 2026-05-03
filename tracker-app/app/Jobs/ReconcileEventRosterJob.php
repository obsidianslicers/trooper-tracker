<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EventTrooperStatus;
use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Mail\Events\TrooperNextInLine;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Reconciles the event roster after capacity limits change.
 *
 * Walks every shift and re-assigns GOING / STAND_BY in sign-up order so
 * the roster always reflects the current global and per-org limits.
 * Troopers promoted to GOING receive a TrooperNextInLine email; troopers
 * demoted to STAND_BY receive a TrooperManualSelectionStandBy email.
 */
class ReconcileEventRosterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $changed_by,
    ) {}

    public function handle(): void
    {
        $event = $this->event;

        $event->refresh();
        $event->load(['event_organizations', 'event_shifts.event_troopers.trooper']);

        foreach ($event->event_shifts as $shift)
        {
            foreach ([false, true] as $is_handler)
            {
                $this->reconcileGroup($event, $shift, $is_handler);
            }
        }
    }

    private function reconcileGroup($event, $shift, bool $is_handler): void
    {
        $limit_field  = $is_handler ? 'handlers_allowed' : 'troopers_allowed';
        $global_limit = $event->{$limit_field};

        $active = $shift->event_troopers
            ->filter(fn ($et) => $et->is_handler === $is_handler
                && in_array($et->status, [EventTrooperStatus::GOING, EventTrooperStatus::STAND_BY]))
            ->sortBy(EventTrooper::SIGNED_UP_AT)
            ->values();

        $global_going = 0;
        $org_going    = [];

        foreach ($active as $et)
        {
            $org_id    = $et->organization_id;
            $org_limit = null;

            if ($org_id !== null)
            {
                $event_org = $event->event_organizations
                    ->firstWhere(EventOrganization::ORGANIZATION_ID, $org_id);

                $org_limit = $event_org?->{$limit_field};
            }

            $fits_global = $global_limit === null || $global_going < $global_limit;
            $fits_org    = $org_id === null || $org_limit === null || ($org_going[$org_id] ?? 0) < $org_limit;

            $new_status = ($fits_global && $fits_org)
                ? EventTrooperStatus::GOING
                : EventTrooperStatus::STAND_BY;

            if ($et->status !== $new_status)
            {
                $et->status = $new_status;
                $et->save();

                if ($new_status === EventTrooperStatus::GOING)
                {
                    Mail::to($et->trooper->email)->queue(new TrooperNextInLine($et));
                }
                else
                {
                    Mail::to($et->trooper->email)->queue(new TrooperManualSelectionStandBy($et, $this->changed_by));
                }
            }

            if ($new_status === EventTrooperStatus::GOING)
            {
                $global_going++;
                if ($org_id !== null)
                {
                    $org_going[$org_id] = ($org_going[$org_id] ?? 0) + 1;
                }
            }
        }
    }
}
