<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Http\Controllers\Concerns\StreamsCsvDownloads;
use App\Messages\Reports\PageData\CostumeEventSummaryCsvPageData;
use App\Messages\Reports\PageData\CostumeEventSummaryPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowCostumeEventSummaryController extends BaseReportsController
{
    use StreamsCsvDownloads;

    public function __invoke(Request $request): Response|StreamedResponse
    {
        if ($request->input('format') === 'csv')
        {
            $csv = CostumeEventSummaryCsvPageData::call($request);

            return $this->streamCsv($csv['filename'], $csv['rows']);
        }

        return Inertia::render('admin/reports/CostumeEventSummary', CostumeEventSummaryPageData::call($request));
    }
}
