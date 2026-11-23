<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\Commands\ShareEventRosterRequest;
use App\Messages\Events\Commands\ShareEventRoster;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * Emails the event's coordinator a read-only link to every shift's roster, from the event
 * page's Share roster dialog. A bad address comes back as a field error.
 */
class ShareEventRosterController extends PageController
{
    use AnswersWithEventPage;

    public function __invoke(ShareEventRosterRequest $request, Event $event): RedirectResponse
    {
        ShareEventRoster::call(
            event: $event,
            recipient_email: $request->validated('recipient_email'),
            shared_by_trooper: $request->user(),
        );

        return $this->backToEvent($event, __('events.Roster successfully sent.'));
    }
}
