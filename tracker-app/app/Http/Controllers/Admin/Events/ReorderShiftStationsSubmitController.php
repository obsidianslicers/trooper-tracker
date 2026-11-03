<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Messages\Events\Commands\ReorderEventShiftStations;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReorderShiftStationsSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): JsonResponse
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ]);

        ReorderEventShiftStations::call(
            event_shift: $event_shift,
            ordered_ids: $validated['ids'] ?? [],
        );

        return response()->json(['success' => true]);
    }
}
