<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Messages\Events\Commands\UpdateEventRoster;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class UpdateTroopersSubmitController extends PageController
{
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        UpdateEventRoster::call(
            event: $event,
            auth_trooper: $request->user(),
            validated_troopers: $request->validated('troopers', []),
            validated_guests: $request->validated('guests', []),
            allowed_org_ids: $request->user()->resolveModeratorOrgIds(),
        );

        FlashType::updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
