<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Messages\Events\Commands\ToggleEventUploadType;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class ToggleUploadTypeController extends EventCommandController
{
    public function __invoke(Request $request, Event $event, EventUpload $event_upload): InertiaResponse
    {
        $this->authorize('update', $event);

        abort_if($event_upload->event_id !== $event->id, 403);

        ToggleEventUploadType::call(event_upload: $event_upload);

        return $this->respondWithResults(
            $request,
            AdminEventPageData::UPLOADS,
            AdminEventPageData::MISSION_REVIEW,
        );
    }
}
