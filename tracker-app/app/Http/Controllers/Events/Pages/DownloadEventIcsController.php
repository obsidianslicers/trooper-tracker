<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\EventIcsPageData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadEventIcsController extends PageController
{
    public function __invoke(Request $request): StreamedResponse
    {
        ['filename' => $filename, 'content' => $content] = EventIcsPageData::call($request);

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
