<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UpdateTroopersController extends MagicBusController
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
        $event_shifts = $this->bus->send(new GetTroopersForEventAdminQuery($event, $allowed_org_ids));

        return view('pages.admin.events.troopers', compact('event', 'event_shifts'));
    }
}
