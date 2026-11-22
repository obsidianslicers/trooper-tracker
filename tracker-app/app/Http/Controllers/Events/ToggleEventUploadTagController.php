<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Models\EventUpload;
use App\Models\Trooper;
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
        /** @var Trooper $trooper */
        $trooper = $request->user();

        // Ensure the event relationship is loaded for rendering and safety.
        $event = $event_upload->event;

        if (! $event)
        {
            abort(404);
        }

        if ($event_upload->is_administrative)
        {
            abort(403);
        }

        // Check if the trooper is already tagged on this upload.
        // Use fully-qualified column to avoid ambiguous `id` between
        // `tt_troopers` and the pivot table.
        $isTagged = $event_upload->troopers()
            ->where('tt_troopers.id', $trooper->id)
            ->exists();

        if ($isTagged)
        {
            // Hard-delete the pivot row so this photo no longer counts as tagged.
            $event_upload->troopers()->detach($trooper->id);
        }
        else
        {
            // Attach the trooper to this upload.
            $event_upload->troopers()->attach($trooper->id);
        }

        return $this->backToEvent($event, $isTagged
            ? __('events.You are no longer tagged in this photo.')
            : __('events.You are now tagged in this photo.'));
    }
}
