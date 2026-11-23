<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Events\Commands\StoreEventRequest;
use App\Messages\Events\Commands\CreateEvent;
use Illuminate\Http\RedirectResponse;

/**
 * Creates an event from the Create Event form and opens it for editing.
 */
class StoreEventController extends PageController
{
    public function __invoke(StoreEventRequest $request): RedirectResponse
    {
        $event = CreateEvent::call(data: $request->validated());

        FlashType::created($event);

        return redirect()->route('admin.events.edit', compact('event'));
    }
}
