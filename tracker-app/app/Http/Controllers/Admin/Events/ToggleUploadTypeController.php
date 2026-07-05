<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Features\Events\Commands\ToggleEventUploadTypeCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ToggleUploadTypeController extends MagicBusController
{
    protected function initialized(): void {}

    public function __invoke(Request $request, Event $event, EventUpload $event_upload): View
    {
        $this->authorize('update', $event);

        abort_if($event_upload->event_id !== $event->id, 403);

        $was_administrative = (bool) $event_upload->is_administrative;

        $this->bus->send(new ToggleEventUploadTypeCommand($event_upload));

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
