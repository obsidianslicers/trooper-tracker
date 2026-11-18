<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the Command Staff event page.
 *
 * A single page carries every tab (overview, shifts, charity, roster, uploads and
 * mission review); each tab posts back to its own command controller.
 */
class UpdateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Events'), 'admin.events.list');
    }

    /**
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event being managed
     */
    public function __invoke(Request $request, Event $event): InertiaResponse
    {
        $this->authorize('update', $event);

        return Inertia::render('admin/events/Update', AdminEventPageData::call($request));
    }
}
