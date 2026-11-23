<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventWatchOutcome;
use App\Events\EventWatchChanging;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Event as EventFacade;

/**
 * Starts or stops a trooper watching an event. A listener on EventWatchChanging may refuse it.
 *
 * @method static EventWatchOutcome call(Event $event, Trooper $trooper)
 */
final class ToggleEventWatch extends Message
{
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
    ) {}

    public function handle(): EventWatchOutcome
    {
        $existing = EventWatch::where('event_id', $this->event->id)
            ->where('trooper_id', $this->trooper->id)
            ->first();

        if (!$this->mayChange($existing === null))
        {
            return EventWatchOutcome::UNCHANGED;
        }

        if ($existing)
        {
            $existing->delete();

            return EventWatchOutcome::NOT_WATCHING;
        }

        EventWatch::create([
            'event_id' => $this->event->id,
            'trooper_id' => $this->trooper->id,
        ]);

        return EventWatchOutcome::WATCHING;
    }

    private function mayChange(bool $watching): bool
    {
        $changing = new EventWatchChanging($this->event, $this->trooper, $watching);

        return EventFacade::until($changing) !== false;
    }
}
