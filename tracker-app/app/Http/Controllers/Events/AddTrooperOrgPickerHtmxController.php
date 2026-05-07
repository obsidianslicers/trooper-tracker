<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddTrooperOrgPickerHtmxController
{
    public function __invoke(Request $request, EventShift $event_shift): Response
    {
        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));

        $event = $event_shift->event;
        $event->loadMissing('event_organizations');

        $eligible_orgs = $trooper->eligibleOrgsForEvent($event);

        return response()->view('pages.events.inc.add-trooper-org-picker', [
            'event_shift' => $event_shift,
            'trooper' => $trooper,
            'eligible_orgs' => $eligible_orgs,
            'is_handler' => (bool) $request->input('is_handler', false),
        ]);
    }
}
