<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use Spatie\CalendarLinks\Generators\Ics;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEventIcsController extends MagicBusController
{
    public function __invoke(Event $event): StreamedResponse
    {
        $link = $event->createCalendarLink();
        $generator = new Ics([], ['format' => 'file']);
        $icsContent = $generator->generate($link);

        $filename = str($event->name)->slug().'.ics';

        return response()->streamDownload(
            function () use ($icsContent) {
                echo $icsContent;
            },
            $filename,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
