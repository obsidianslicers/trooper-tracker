<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Messages\Events\Commands\RemoveEventShiftStation;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class RemoveShiftStationController extends EventCommandController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift, EventShiftStation $event_shift_station): InertiaResponse
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);
        abort_if($event_shift_station->event_shift_id !== $event_shift->id, 404);

        $removed = RemoveEventShiftStation::call(event_shift_station: $event_shift_station);

        if (!$removed)
        {
            FlashType::danger(__('admin.Cannot remove a station with roster signups.'));

            return $this->respondWithResults($request);
        }

        FlashType::success(__('admin.Station removed.'));

        return $this->respondWithResults($request);
    }
}
