<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the event copy form.
 *
 * Provides administrators and moderators with a form to copy an existing event.
 * The copied event will have "COPY OF" prepended to its name and can be modified
 * before submission. This allows easy creation of similar events without manual
 * data entry.
 */
class CopyController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * Displays the event copy form with prepopulated data
     *
     * Authorizes that the user can update the event via policy check.
     * Prepends "COPY OF" to the event name and renders the copy form view
     * with all other event data from the source event.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The source event to be copied (route model binding)
     * @return View The event copy form view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event->name = 'COPY OF '.$event->name;

        $data = compact('event');

        return view('pages.admin.events.copy', $data);
    }
}
