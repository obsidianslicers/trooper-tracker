<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Messages\Events\Commands\ToggleEventUploadTag;
use App\Models\EventUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Toggle the current trooper's tag on an event upload photo.
 *
 * This controller lets troopers mark themselves as appearing in a given
 * event upload ("Tag me") or remove that association ("Untag me").
 * It goes back to the event page with a toast.
 */
class ToggleEventUploadTagController
{
    use AnswersWithEventPage;

    /**
     * Toggle the authenticated trooper's tag on the given upload.
     */
    public function __invoke(Request $request, EventUpload $event_upload): RedirectResponse
    {
        $event = $event_upload->event;

        if (! $event)
        {
            abort(404);
        }

        if ($event_upload->is_administrative)
        {
            abort(403);
        }

        $is_tagged = ToggleEventUploadTag::call(event_upload: $event_upload, trooper: $request->user());

        return $this->backToEvent($event, $is_tagged
            ? __('events.You are now tagged in this photo.')
            : __('events.You are no longer tagged in this photo.'));
    }
}
