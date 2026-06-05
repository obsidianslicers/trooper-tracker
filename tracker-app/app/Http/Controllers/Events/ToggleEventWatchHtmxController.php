<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Facades\TroopTrackerFacade;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use App\Services\Forums\XenforoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ToggleEventWatchHtmxController
{
    public function __invoke(
        Request $request,
        Event $event,
        XenforoService $xenforo,
        FlashMessageService $flash
    ): Response {
        /** @var Trooper $trooper */
        $trooper = $request->user();

        $existing = EventWatch::where('event_id', $event->id)
            ->where('trooper_id', $trooper->id)
            ->first();

        if ($existing)
        {
            if (!$this->syncXenforoWatch($event, $trooper, false, $xenforo, $flash))
            {
                return response()->view('pages.events.inc.watch-toggle', ['event' => $event, 'is_watching' => true]);
            }

            $existing->delete();
            $is_watching = false;
        }
        else
        {
            if (!$this->syncXenforoWatch($event, $trooper, true, $xenforo, $flash))
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

    private function syncXenforoWatch(
        Event $event,
        Trooper $trooper,
        bool $watch,
        XenforoService $xenforo,
        FlashMessageService $flash
    ): bool {
        if (!TroopTrackerFacade::isXenforoIntegrationConfigured() || empty($event->thread_id))
        {
            return true;
        }

        $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($trooper->id);

        if ($xenforo_user_id === null)
        {
            return true;
        }

        $result = $watch
            ? $xenforo->watch_thread((int) $event->thread_id, $xenforo_user_id)
            : $xenforo->unwatch_thread((int) $event->thread_id, $xenforo_user_id);

        $status = $result['status'] ?? 0;

        if ($status < 200 || $status >= 300)
        {
            $flash->danger('Unable to update your forum thread subscription. Please try again.');

            return false;
        }

        return true;
    }
}
