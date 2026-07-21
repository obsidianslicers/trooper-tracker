<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\RemoveEventShiftStationCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RemoveShiftStationController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift, EventShiftStation $event_shift_station): Response
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);
        abort_if($event_shift_station->event_shift_id !== $event_shift->id, 404);

        $removed = $this->bus->send(new RemoveEventShiftStationCommand($event_shift_station));

        if (!$removed)
        {
            $this->flash->danger('Cannot remove a station with roster signups.');

            return response()->noContent()->header('HX-Redirect', route('admin.events.shifts', compact('event')));
        }

        $this->flash->success('Station removed.');

        return response()->noContent()->header('HX-Redirect', route('admin.events.shifts', compact('event')));
    }
}
