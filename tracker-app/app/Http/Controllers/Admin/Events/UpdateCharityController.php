<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UpdateCharityController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Events'), 'admin.events.list');
    }

    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $shifts = $event->event_shifts()->orderBy(EventShift::SHIFT_STARTS_AT)->get();

        return view('pages.admin.events.charity', compact('event', 'shifts'));
    }
}
