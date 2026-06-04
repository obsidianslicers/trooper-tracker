<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\DeleteEventUploadCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles deletion of an event photo upload.
 *
 * Soft-deletes the upload record, removes its trooper tags, and deletes the
 * associated files from storage.
 */
class DeleteUploadController extends MagicBusController
{
    protected function initialized(): void {}

    /**
     * @param  Request      $request
     * @param  Event        $event
     * @param  EventUpload  $event_upload
     * @return Response
     */
    public function __invoke(Request $request, Event $event, EventUpload $event_upload): Response
    {
        $this->authorize('update', $event);

        abort_if($event_upload->event_id !== $event->id, 403);

        $this->bus->send(new DeleteEventUploadCommand($event_upload));

        return response()->noContent();
    }
}
