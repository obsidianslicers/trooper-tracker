<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Enums\AddEventTrooperOutcome;
use App\Enums\FlashType;
use App\Messages\Events\Commands\AddEventTrooper;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class StoreEventTrooperController extends EventCommandController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): InertiaResponse
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));

        $outcome = AddEventTrooper::call(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $request->user(),
            costume_id: $this->optionalId($request, 'costume_id'),
            organization_id: $this->optionalId($request, 'organization_id'),
            event_shift_station_id: $this->optionalId($request, 'event_shift_station_id'),
        );

        match ($outcome)
        {
            AddEventTrooperOutcome::ALREADY_SIGNED_UP => FlashType::danger(__(
                'admin.:name is already signed up for this shift.',
                ['name' => $trooper->display_name]
            )),
            AddEventTrooperOutcome::STATION_REQUIRED => FlashType::danger(
                __('admin.Select a station before adding a trooper.')
            ),
            AddEventTrooperOutcome::ADDED => FlashType::success(__(
                'admin.:name was added to the shift.',
                ['name' => $trooper->display_name]
            )),
        };

        return $this->respondWithResults($request, AdminEventPageData::ROSTER);
    }

    private function optionalId(Request $request, string $key): ?int
    {
        return $request->input($key) ? (int) $request->input($key) : null;
    }
}
