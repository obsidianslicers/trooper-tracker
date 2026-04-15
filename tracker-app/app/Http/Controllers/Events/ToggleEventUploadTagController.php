<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Toggle the current trooper's tag on an event upload photo.
 *
 * This controller lets troopers mark themselves as appearing in a given
 * event upload ("Tag me") or remove that association ("Untag me").
 * It returns the refreshed upload grid partial for HTMX updates.
 */
class ToggleEventUploadTagController
{
    /**
     * Toggle the authenticated trooper's tag on the given upload.
     */
    public function __invoke(Request $request, EventUpload $event_upload): Response
    {
        /** @var Trooper $trooper */
        $trooper = $request->user();

        // Ensure the event relationship is loaded for rendering and safety.
        $event = $event_upload->event;

        if (! $event)
        {
            abort(404);
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

        // Reload uploads with trooper tags so the view can show updated state.
        $event->load(['event_uploads.troopers']);

        $is_administrative = (bool) $event_upload->is_administrative;

        $flashMessage = json_encode([
            'message' => $isTagged
                ? 'You are no longer tagged in this photo.'
                : 'You are now tagged in this photo.',
            'type' => 'success',
        ]);

        return response()
            ->view('pages.events.inc.upload-display', compact('event', 'is_administrative'))
            ->header('X-Flash-Message', $flashMessage);
    }
}
