<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\EventShiftStation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShiftAddTrooperComposer
{
    public function compose(View $view): void
    {
        $event = $view->getData()['event'];
        $event_shift = $view->getData()['event_shift'];

        $eligible_orgs = Auth::user()->eligibleOrgsForEvent($event);

        $limited_org_ids = $event->event_organizations()
            ->where(function ($q) {
                $q->whereNotNull('troopers_allowed')->orWhereNotNull('handlers_allowed');
            })
            ->pluck('organization_id')
            ->toArray();

        //  the Sign Up form: their costume (preselected) and, where the event allows it, Tentative
        $signup_costume_options = [];
        $signup_costume_default = null;
        $signup_status_options = [];

        if ($event_shift->canSignUp(Auth::user()))
        {
            $signup_costume_options = $event_shift->costumeOptionsFor(Auth::user());
            $signup_costume_default = $event_shift->defaultCostumeIdFor(Auth::user(), $signup_costume_options);

            if ($event->status !== EventStatus::MANUAL_SELECTION && $event->allowsTentativeSignups() && !$event->hasLimits())
            {
                $signup_status_options = [
                    EventTrooperStatus::GOING->value => EventTrooperStatus::GOING->label(),
                    EventTrooperStatus::TENTATIVE->value => EventTrooperStatus::TENTATIVE->label(),
                ];
            }
        }

        $view->with([
            'signup_costume_options'         => $signup_costume_options,
            'signup_costume_default'         => $signup_costume_default,
            'signup_status_options'          => $signup_status_options,
            'requires_mission_brief_ack'     => $event->require_mission_brief_ack,
            'has_required_mission_brief_ack' => !$event->require_mission_brief_ack || $event->hasMissionBriefAcknowledgementFor(Auth::user()),
            'eligible_orgs'                  => $eligible_orgs,
            'event_has_org_limits'           => $eligible_orgs->whereIn('id', $limited_org_ids)->isNotEmpty(),
            'limited_orgs_for_add'           => $event->organizations
                ->filter(fn ($o) => $o->pivot->can_attend && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null))
                ->values(),
            'event_allows_handlers'          => $event->handlers_allowed !== 0,
            'station_options'                => $event_shift->event_shift_stations
                ->sortBy([
                    [EventShiftStation::SEQUENCE, 'asc'],
                    [EventShiftStation::NAME, 'asc'],
                ])
                ->pluck(EventShiftStation::NAME, EventShiftStation::ID)
                ->toArray(),
            'shift_uses_stations'            => $event_shift->usesStations(),
        ]);
    }
}
