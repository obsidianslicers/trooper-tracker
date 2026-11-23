<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Http\Requests\Events\Commands\BroadcastEventMessageRequest;
use App\Jobs\BroadcastEventMessageJob;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * Sends an instant message to everyone going to the event, from the event page.
 */
class BroadcastEventMessageController extends PageController
{
    use AnswersWithEventPage;

    public function __invoke(BroadcastEventMessageRequest $request, Event $event): RedirectResponse
    {
        dispatch(new BroadcastEventMessageJob($event, $request->validated('message')));

        return $this->backToEvent($event, __('events.Message sent.'));
    }
}
