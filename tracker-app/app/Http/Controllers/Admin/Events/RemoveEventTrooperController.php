<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\PromoteNextInLineEventTrooper;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RemoveEventTrooperController extends PageController
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): Response
    {
        $this->authorize('update', $event);

        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $trooper_name = $event_trooper->trooper->display_name;

        DB::transaction(function () use ($event_trooper)
        {
            if ($event_trooper->intendsToGo())
            {
                PromoteNextInLineEventTrooper::call(
                    event_trooper: $event_trooper,
                    event_shift_station_id: $event_trooper->event_shift_station_id,
                );
            }

            $event_trooper->delete();
        });

        FlashType::success("{$trooper_name} was removed from the roster.");

        return response()->noContent()->header('HX-Redirect', route('admin.events.troopers', compact('event')));
    }
}
