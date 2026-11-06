<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\Queries\GetTroopersForEventAdmin;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UpdateTroopersController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $allowed_org_ids = $request->user()->resolveModeratorOrgIds();
        $event_shifts = GetTroopersForEventAdmin::call(event: $event, allowed_org_ids: $allowed_org_ids);

        return view('pages.admin.events.troopers', compact('event', 'event_shifts'));
    }
}
