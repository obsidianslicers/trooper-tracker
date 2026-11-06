<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Events\EventWatchChanging;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event as EventFacade;

class ToggleEventWatchHtmxController
{
    public function __invoke(
        Request $request,
        Event $event,
    ): Response {
        /** @var Trooper $trooper */
        $trooper = $request->user();

        $existing = EventWatch::where('event_id', $event->id)
            ->where('trooper_id', $trooper->id)
            ->first();

        if ($existing)
        {
            if (!$this->mayChange($event, $trooper, false))
            {
                return response()->view('pages.events.inc.watch-toggle', ['event' => $event, 'is_watching' => true]);
            }

            $existing->delete();
            $is_watching = false;
        }
        else
        {
            if (!$this->mayChange($event, $trooper, true))
            {
                return response()->view('pages.events.inc.watch-toggle', ['event' => $event, 'is_watching' => false]);
            }

            EventWatch::create([
                'event_id' => $event->id,
                'trooper_id' => $trooper->id,
            ]);
            $is_watching = true;
        }

        return response()->view('pages.events.inc.watch-toggle', compact('event', 'is_watching'));
    }

    /**
     * Listeners, such as a forum plugin keeping its thread subscription in step,
     * can stop the change by returning false.
     */
    private function mayChange(Event $event, Trooper $trooper, bool $watching): bool
    {
        return EventFacade::until(new EventWatchChanging($event, $trooper, $watching)) !== false;
    }
}
