<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the mission review page for an event.
 *
 * Provides administrators and moderators with an interface to review and
 * moderate member-submitted event photos.
 */
class MissionReviewController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Events', 'admin.events.list');
    }

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
