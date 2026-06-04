<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the mission review page for an event.
 *
 * Provides administrators and moderators with an interface to review and
 * moderate member-submitted event photos.
 */
class MissionReviewController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

    /**
     * @param  Request  $request
     * @param  Event    $event
     * @return View
     */
    public function __invoke(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        $member_uploads = $event->event_uploads()
            ->where('is_administrative', false)
            ->with('trooper', 'troopers')
            ->latest()
            ->get();

        $data = compact('event', 'member_uploads');

        return view('pages.admin.events.mission-review', $data);
    }
}
