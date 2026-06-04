<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\DeleteEventUploadCommand;
use App\Http\Controllers\MagicBusController;
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
class DeleteUploadController extends MagicBusController
{
    protected function initialized(): void {}

    public function __invoke(Request $request, Event $event, EventUpload $event_upload): View
    {
        $this->authorize('update', $event);

        abort_if($event_upload->event_id !== $event->id, 403);

        $this->bus->send(new DeleteEventUploadCommand($event_upload));

        $member_uploads = $event->event_uploads()
            ->where('is_administrative', false)
            ->with('trooper', 'troopers')
            ->latest()
            ->get();

        return view('pages.admin.events.mission-review', compact('event', 'member_uploads'));
    }
}
