<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Events\CopyRequest;
use App\Messages\Events\Commands\CopyEvent;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Copies an event, its shifts and clubs as a draft starting at the new date.
 */
class CopySubmitController extends PageController
{
    public function __invoke(CopyRequest $request, Event $event): RedirectResponse
    {
        $copy = CopyEvent::call(
            event: $event,
            name: $request->validated(Event::NAME),
            event_start: Carbon::parse($request->validated(Event::EVENT_START)),
        );

        FlashType::updated($copy);

        return redirect()->route('admin.events.update', ['event' => $copy]);
    }
}
