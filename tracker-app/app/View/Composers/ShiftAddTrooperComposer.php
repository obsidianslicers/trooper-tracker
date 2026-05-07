<?php

declare(strict_types=1);

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShiftAddTrooperComposer
{
    public function compose(View $view): void
    {
        $event = $view->getData()['event'];

        $eligible_orgs = Auth::user()->eligibleOrgsForEvent($event);

        $limited_org_ids = $event->event_organizations()
            ->where(function ($q) {
                $q->whereNotNull('troopers_allowed')->orWhereNotNull('handlers_allowed');
            })
            ->pluck('organization_id')
            ->toArray();

        $view->with([
            'requires_mission_brief_ack'     => $event->require_mission_brief_ack,
            'has_required_mission_brief_ack' => !$event->require_mission_brief_ack || $event->hasMissionBriefAcknowledgementFor(Auth::user()),
            'eligible_orgs'                  => $eligible_orgs,
            'event_has_org_limits'           => $eligible_orgs->whereIn('id', $limited_org_ids)->isNotEmpty(),
            'limited_orgs_for_add'           => $event->organizations
                ->filter(fn ($o) => $o->pivot->can_attend && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null))
                ->values(),
            'event_allows_handlers'          => $event->handlers_allowed !== 0,
        ]);
    }
}
