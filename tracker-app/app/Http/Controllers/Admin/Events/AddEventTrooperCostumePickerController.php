<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Messages\Events\Resources\AddEventTrooperChoices;
use App\Models\Event;
use App\Models\EventShift;
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

        return (new AddEventTrooperChoices($trooper, $event, $event_shift))->response($request);
    }
}
