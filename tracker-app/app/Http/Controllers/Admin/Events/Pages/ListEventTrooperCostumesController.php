<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Events\PageData\Admin\EventTrooperCostumesPageData;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The costume, club and station choices for adding a trooper to a shift's roster.
 */
class ListEventTrooperCostumesController extends Controller
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): JsonResponse
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        return EventTrooperCostumesPageData::call(
            event: $event,
            event_shift: $event_shift,
            trooper_id: (int) $request->input('trooper_id'),
        )->response($request);
    }
}
