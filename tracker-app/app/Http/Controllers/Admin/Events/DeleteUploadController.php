<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\DeleteEventUpload;
use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles deletion of an event photo upload.
 *
 * Soft-deletes the upload record, removes its trooper tags, and deletes the
 * associated files from storage.
 */
class DeleteUploadController extends PageController
{
    protected function initialized(): void {}

    public function __invoke(Request $request, Event $event, EventUpload $event_upload): View
    {
        $this->authorize('update', $event);

        abort_if($event_upload->event_id !== $event->id, 403);

        $was_administrative = (bool) $event_upload->is_administrative;

        DeleteEventUpload::call(event_upload: $event_upload);

        if ($was_administrative)
        {
            $event->load(['event_uploads.trooper', 'event_uploads.troopers']);

            return view('pages.admin.events.uploads', compact('event'));
        }

        $member_uploads = $event->event_uploads()
            ->where(EventUpload::IS_ADMINISTRATIVE, false)
            ->with('trooper', 'troopers')
            ->latest()
            ->get();

        return view('pages.admin.events.mission-review', compact('event', 'member_uploads'));
    }
}
