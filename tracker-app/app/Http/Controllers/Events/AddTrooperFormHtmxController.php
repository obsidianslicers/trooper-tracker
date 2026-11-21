<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Add a Friend, step 2: once a trooper is picked, the form that signs them up in one go, like
 * the trooper's own Sign Up form: their costume (preselected when we can guess it) and, when
 * the shift needs them, their organization and station.
 */
class AddTrooperFormHtmxController
{
    public function __invoke(Request $request, EventShift $event_shift): Response
    {
        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));

        $event = $event_shift->event;
        $event->loadMissing('event_organizations');
        $event_shift->loadMissing('event_shift_stations');

        //  their organization only matters when an organization on the event has its own limits
        $event_has_org_limits = $event->organizations
            ->contains(fn ($o) => $o->pivot->can_attend && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null));

        $costume_options = $event_shift->costumeOptionsFor($trooper);

        return response()->view('pages.events.inc.add-trooper-form', [
            'event_shift' => $event_shift,
            'trooper' => $trooper,
            'eligible_orgs' => $event_has_org_limits ? $trooper->eligibleOrgsForEvent($event) : collect(),
            'costume_options' => $costume_options,
            'costume_default' => $event_shift->defaultCostumeIdFor($trooper, $costume_options),
            'station_options' => $event_shift->event_shift_stations
                ->sortBy([
                    [EventShiftStation::SEQUENCE, 'asc'],
                    [EventShiftStation::NAME, 'asc'],
                ])
                ->pluck(EventShiftStation::NAME, EventShiftStation::ID)
                ->toArray(),
        ]);
    }
}
