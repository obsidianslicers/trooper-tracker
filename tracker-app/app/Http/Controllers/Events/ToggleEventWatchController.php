<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Events\EventWatchChanging;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event as EventFacade;

/**
 * Watches or stops watching an event from its page.
 */
class ToggleEventWatchController
{
    use AnswersWithEventPage;

    public function __invoke(
        Request $request,
        Event $event,
    ): RedirectResponse {
        /** @var Trooper $trooper */
        $trooper = $request->user();

        $existing = EventWatch::where('event_id', $event->id)
            ->where('trooper_id', $trooper->id)
            ->first();

        if ($existing)
        {
            if (!$this->mayChange($event, $trooper, false))
            {
                return $this->backToEvent($event);
            }

            $existing->delete();

            return $this->backToEvent($event, __("events.You're no longer watching this event."));
        }
        else
        {
            if (!$this->mayChange($event, $trooper, true))
            {
                return $this->backToEvent($event);
            }

            EventWatch::create([
                'event_id' => $event->id,
                'trooper_id' => $trooper->id,
            ]);
        }

        return $this->backToEvent($event, __("events.You're watching this event."));
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
