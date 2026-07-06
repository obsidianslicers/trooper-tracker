<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddEventTrooperController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): Response
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        $trooper_id = (int) $request->input('trooper_id');

        $trooper = Trooper::active()->findOrFail($trooper_id);

        $auth_trooper = $request->user();
        $event_shift->loadMissing('event_shift_stations');

        if ($event_shift->isSignedUp($trooper))
        {
            $this->flash->danger("{$trooper->display_name} is already signed up for this shift.");

            return response()->noContent()->header('HX-Redirect', route('admin.events.troopers', compact('event')));
        }

        $costume_id = $request->input('costume_id') ? (int) $request->input('costume_id') : null;

        if ($costume_id !== null && !Costume::forTrooper($trooper->id)->where('id', $costume_id)->exists())
        {
            $costume_id = null;
        }

        $organization_id = $request->input('organization_id') ? (int) $request->input('organization_id') : null;

        if ($organization_id !== null)
        {
            $eligible_org_ids = $trooper->eligibleOrgsForEvent($event)->pluck('id')->all();

            if (!in_array($organization_id, $eligible_org_ids, true))
            {
                $organization_id = null;
            }
        }

        $event_shift_station_id = $request->input('event_shift_station_id')
            ? (int) $request->input('event_shift_station_id')
            : null;

        if ($event_shift->usesStations())
        {
            $valid_station = $event_shift_station_id !== null
                && $event_shift->event_shift_stations->contains('id', $event_shift_station_id);

            if (!$valid_station)
            {
                $this->flash->danger('Select a station before adding a trooper.');

                return response()->noContent()->header('HX-Redirect', route('admin.events.troopers', compact('event')));
            }
        }

        $this->bus->send(new SignUpEventTrooperCommand(
            $event_shift,
            $trooper,
            $auth_trooper,
            organization_id: $organization_id,
            costume_id: $costume_id,
            event_shift_station_id: $event_shift_station_id,
        ));

        $this->flash->success("{$trooper->display_name} was added to the shift.");

        return response()->noContent()->header('HX-Redirect', route('admin.events.troopers', compact('event')));
    }
}
