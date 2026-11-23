<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports\Pages;

use App\Http\Controllers\Concerns\StreamsCsvDownloads;
use App\Messages\Reports\PageData\DonationEventSummaryCsvPageData;
use App\Messages\Reports\PageData\DonationEventSummaryPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowDonationEventSummaryController extends BaseReportsController
{
    use StreamsCsvDownloads;

    public function __invoke(Request $request): Response|StreamedResponse
    {
        if ($request->input('format') === 'csv')
        {
            $csv = DonationEventSummaryCsvPageData::call($request);

            return $this->streamCsv($csv['filename'], $csv['rows']);
        }

        return Inertia::render('admin/reports/DonationEventSummary', DonationEventSummaryPageData::call($request));
    }
}
