<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\EventShiftIcsPageData;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEventShiftIcsController extends PageController
{
    public function __invoke(Request $request, Event $event, EventShift $event_shift): StreamedResponse
    {
        abort_unless((int) $event_shift->event_id === (int) $event->id, 404);

        ['filename' => $filename, 'content' => $content] = EventShiftIcsPageData::call($request);

        return response()->streamDownload(
            function () use ($content)
            {
                echo $content;
            },
            $filename,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
