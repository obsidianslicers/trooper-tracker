<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventWatchOutcome;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Messages\Events\Commands\ToggleEventWatch;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Watches or stops watching an event from its page.
 */
class ToggleEventWatchController
{
    use AnswersWithEventPage;

    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $outcome = ToggleEventWatch::call(event: $event, trooper: $request->user());

        return $this->backToEvent($event, match ($outcome)
        {
            EventWatchOutcome::WATCHING => __("events.You're watching this event."),
            EventWatchOutcome::NOT_WATCHING => __("events.You're no longer watching this event."),
            EventWatchOutcome::UNCHANGED => null,
        });
    }
}
