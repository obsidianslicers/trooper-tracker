<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The costume, club and station choices for adding a trooper to a shift's roster.
 */
class AddEventTrooperCostumePickerController extends Controller
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): JsonResponse
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));
        $event_shift->loadMissing('event_shift_stations');

        return response()->json([
            'trooper_id' => $trooper->id,
            'display_name' => $trooper->display_name,
            'costume_options' => Costume::forTrooper($trooper->id)
                ->pluck(Costume::NAME, Costume::ID)
                ->map(fn (string $name, int $id): array => ['value' => $id, 'label' => $name])
                ->values(),
            'organization_options' => $trooper->eligibleOrgsForEvent($event)
                ->map(fn (Organization $org): array => ['value' => $org->id, 'label' => $org->name])
                ->values(),
            'station_options' => $event_shift->event_shift_stations
                ->map(fn (EventShiftStation $s): array => ['value' => $s->id, 'label' => $s->name])
                ->values(),
        ]);
    }
}
