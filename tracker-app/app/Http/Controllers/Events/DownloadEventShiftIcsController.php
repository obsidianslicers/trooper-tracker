<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventShift;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEventShiftIcsController extends MagicBusController
{
    public function __invoke(Event $event, EventShift $event_shift): StreamedResponse
    {
        abort_unless((int) $event_shift->event_id === (int) $event->id, 404);

        $link = $event_shift->createCalendarLink();
        $generator = new \Spatie\CalendarLinks\Generators\Ics([], ['format' => 'file']);
        $icsContent = $generator->generate($link);

        $filename = str($event->name)->slug() . '-shift-' . $event_shift->id . '.ics';

        return response()->streamDownload(
            function () use ($icsContent) {
                echo $icsContent;
            },
            $filename,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
