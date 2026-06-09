<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\RemoveEventTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\RemoveEventTrooperRequest;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Http\RedirectResponse;

class RemoveEventTrooperController extends MagicBusController
{
    public function __invoke(RemoveEventTrooperRequest $request, Event $event, EventTrooper $event_trooper): RedirectResponse
    {
        $this->bus->send(new RemoveEventTrooperCommand($event_trooper));

        $this->flash->success("Removed {$event_trooper->trooper->display_name} from the roster.");

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
