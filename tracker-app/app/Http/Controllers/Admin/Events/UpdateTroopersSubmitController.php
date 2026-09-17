<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\UpdateEventRosterCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class UpdateTroopersSubmitController extends MagicBusController
{
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->bus->send(new UpdateEventRosterCommand(
            event: $event,
            auth_trooper: $request->user(),
            validated_troopers: $request->validated('troopers', []),
            validated_guests: $request->validated('guests', []),
            allowed_org_ids: $request->user()->resolveModeratorOrgIds(),
        ));

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
