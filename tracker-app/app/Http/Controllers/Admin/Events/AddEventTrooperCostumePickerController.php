<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AddEventTrooperCostumePickerController extends MagicBusController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): View
    {
        $this->authorize('update', $event);

        abort_if($event_shift->event_id !== $event->id, 404);

        $trooper = Trooper::active()->findOrFail((int) $request->input('trooper_id'));

        $costumes = Costume::forTrooper($trooper->id)->pluck('name', 'id')->toArray();

        return view('pages.admin.events.inc.add-trooper-costume-picker', compact(
            'event',
            'event_shift',
            'trooper',
            'costumes',
        ));
    }
}
