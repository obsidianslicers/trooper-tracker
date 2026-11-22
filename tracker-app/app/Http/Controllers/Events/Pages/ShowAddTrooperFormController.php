<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Add a Friend, step 2: once a trooper is picked, what to choose to sign them up in one go,
 * like the trooper's own Sign Up: their costume (preselected when we can guess it) and,
 * when the shift needs them, their organization and station.
 */
class ShowAddTrooperFormController
{
    public function __invoke(Request $request, EventShift $event_shift): JsonResponse
    {
        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));

        $event = $event_shift->event;
        $event->loadMissing('event_organizations');
        $event_shift->loadMissing('event_shift_stations');

        //  their organization only matters when an organization on the event has its own limits
        $event_has_org_limits = $event->organizations
            ->contains(fn ($o) => $o->pivot->can_attend
                && ($o->pivot->troopers_allowed !== null || $o->pivot->handlers_allowed !== null));

        $costume_options = $event->requiresCostume() ? $event_shift->costumeOptionsFor($trooper) : [];
        $costume_default = $event->requiresCostume()
            ? $event_shift->defaultCostumeIdFor($trooper, $costume_options) : null;

        return response()->json([
            'trooper' => ['id' => $trooper->id, 'name' => $trooper->display_name],
            'organizations' => ($event_has_org_limits ? $trooper->eligibleOrgsForEvent($event) : collect())
                ->map(fn (Organization $o) => ['value' => $o->id, 'label' => $o->name])
                ->values(),
            'stations' => $event_shift->event_shift_stations
                ->sortBy([[EventShiftStation::SEQUENCE, 'asc'], [EventShiftStation::NAME, 'asc']])
                ->map(fn (EventShiftStation $s) => ['value' => $s->id, 'label' => $s->name])
                ->values(),
            'requires_costume' => $event->requiresCostume(),
            'costumes' => collect($costume_options)
                ->map(fn (string $name, int $id) => ['value' => (string) $id, 'label' => $name])
                ->values(),
            'default_costume' => $event->requiresCostume()
                ? ($costume_default !== null ? (string) $costume_default : '') : 'none',
        ]);
    }
}
