<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Messages\Events\Commands\ToggleEventUploadType;
use App\Models\EventUpload;
use Illuminate\Http\Response;

/**
 * Moves a photo between Instructional Uploads and Mission Review from the event page.
 */
class ToggleUploadTypeController extends Controller
{
    public function __invoke(EventUpload $event_upload): Response
    {
        $this->authorize('changeType', $event_upload);

        $event_upload = ToggleEventUploadType::call(event_upload: $event_upload);

        $event = $event_upload->event;
        $event->load(['event_uploads.troopers', 'event_uploads.trooper']);

        $flash = json_encode([
            'message' => $event_upload->is_administrative
                ? __('events.Photo moved to Instructional Uploads.')
                : __('events.Photo moved to Mission Review.'),
            'type' => 'success',
        ]);

        return response()
            ->view('pages.events.inc.uploads-panel', compact('event'))
            ->header('X-Flash-Message', $flash);
    }
}
