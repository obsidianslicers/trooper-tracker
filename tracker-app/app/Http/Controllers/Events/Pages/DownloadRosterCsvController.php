<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\Concerns\StreamsCsvDownloads;
use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\RosterCsvPageData;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadRosterCsvController extends PageController
{
    use StreamsCsvDownloads;

    public function allShifts(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('update', $event);

        ['filename' => $filename, 'rows' => $rows] = RosterCsvPageData::call($request);

        return $this->streamCsv($filename, $rows);
    }

    public function singleShift(Request $request, Event $event, EventShift $event_shift): StreamedResponse
    {
        $this->authorize('update', $event);

        abort_unless((int) $event_shift->event_id === (int) $event->id, 404);

        ['filename' => $filename, 'rows' => $rows] = RosterCsvPageData::call($request);

        return $this->streamCsv($filename, $rows);
    }
}
