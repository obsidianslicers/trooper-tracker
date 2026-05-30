<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ToggleEventWatchHtmxController
{
    public function __invoke(Request $request, Event $event): Response
    {
        /** @var Trooper $trooper */
        $trooper = $request->user();

        $existing = EventWatch::where('event_id', $event->id)
            ->where('trooper_id', $trooper->id)
            ->first();

        if ($existing)
        {
            $existing->delete();
            $is_watching = false;
        }
        else
        {
            EventWatch::create([
                'event_id'   => $event->id,
                'trooper_id' => $trooper->id,
            ]);
            $is_watching = true;
        }

        return response()->view('pages.events.inc.watch-toggle', compact('event', 'is_watching'));
    }
}
