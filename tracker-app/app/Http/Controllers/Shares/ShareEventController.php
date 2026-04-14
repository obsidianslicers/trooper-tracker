<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the event sharing page.
 *
 * This controller is responsible for rendering the event sharing view
 * that allows users to share event information via various channels
 * such as social media, email, or direct links.
 */
class ShareEventController extends MagicBusController
{
    /**
     * Handle the incoming request to display the event sharing page.
     *
     * This method retrieves the specified event and renders the sharing
     * view where users can generate and copy shareable links or share
     * directly to social media platforms.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Event  $event  The event to be shared
     * @return View The rendered sharing page view
     */
    public function __invoke(Request $request, Event $event): View
    {
        $image_id = $request->query('event_upload');

        $image_url = route('shares.og-image', $event->organization->id);

        $event_upload = null;

        if ($image_id)
        {
            $event_upload = $event->event_uploads()
                ->where(EventUpload::ID, $image_id)
                ->first();
        }

        if ($event_upload)
        {
            $image_url = map_image_url($event_upload->image_path_lg, 'img/icons/organization-128x128.png');
        }

        $image_url = url($image_url);

        $data = compact('event', 'event_upload', 'image_url');

        return view('pages.shares.event', $data);
    }
}
