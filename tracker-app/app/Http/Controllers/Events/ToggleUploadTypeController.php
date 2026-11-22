<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Messages\Events\Commands\ToggleEventUploadType;
use App\Models\EventUpload;
use Illuminate\Http\RedirectResponse;

/**
 * Moves a photo between Instructional Uploads and Mission Review from the event page.
 */
class ToggleUploadTypeController extends Controller
{
    use AnswersWithEventPage;

    public function __invoke(EventUpload $event_upload): RedirectResponse
    {
        $this->authorize('changeType', $event_upload);

        $event_upload = ToggleEventUploadType::call(event_upload: $event_upload);

        return $this->backToEvent($event_upload->event, $event_upload->is_administrative
            ? __('events.Photo moved to Instructional Uploads.')
            : __('events.Photo moved to Mission Review.'));
    }
}
