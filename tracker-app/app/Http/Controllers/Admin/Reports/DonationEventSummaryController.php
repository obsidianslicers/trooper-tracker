<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetDonationEventSummaryQuery;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationEventSummaryController extends BaseReportsController
{
    public function __invoke(Request $request): View|StreamedResponse
    {
        $trooper = $request->user();

        $date_start = $request->filled('date_start')
            ? Carbon::parse($request->input('date_start'))->startOfDay()
            : null;

        $date_end = $request->filled('date_end')
            ? Carbon::parse($request->input('date_end'))->endOfDay()
            : null;

        $charity_only = (bool) $request->input('charity_only', false);

        $sort = $request->input('sort', 'event_start');
        $dir  = $request->input('dir', 'desc');

        if ($request->input('format') === 'csv') {
            $all = $this->bus->send(new GetDonationEventSummaryQuery($trooper, $date_start, $date_end, $charity_only, PHP_INT_MAX, $sort, $dir));
            return $this->streamCsv($all, $date_start, $date_end, $charity_only);
        }

        $events = $this->bus->send(new GetDonationEventSummaryQuery($trooper, $date_start, $date_end, $charity_only, 50, $sort, $dir));

        $data = compact('events', 'date_start', 'date_end', 'charity_only', 'sort', 'dir');

        return view('pages.admin.reports.donation-event-summary', $data);
    }

    private function streamCsv(LengthAwarePaginator $events, ?Carbon $date_start, ?Carbon $date_end, bool $charity_only): StreamedResponse
    {
        $filename = 'donation-event-summary-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($events, $date_start, $date_end, $charity_only) {
            $handle = fopen('php://output', 'w');

            $meta = [];
            if ($date_start) {
                $meta[] = 'From: ' . $date_start->format('Y-m-d');
            }
            if ($date_end) {
                $meta[] = 'To: ' . $date_end->format('Y-m-d');
            }
            if ($charity_only) {
                $meta[] = 'Charity Data Only';
            }
            if (!empty($meta)) {
                fputcsv($handle, $meta);
            }

            fputcsv($handle, ['Event', 'Date', 'Club', 'Charity', 'Direct Funds', 'Indirect Funds', 'Total Funds', 'Attendees', 'Notes']);

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->name,
                    $event->event_start?->format('Y-m-d'),
                    $event->organization?->name,
                    $event->charity_name,
                    $event->charity_direct_funds,
                    $event->charity_indirect_funds,
                    $event->charity_direct_funds + $event->charity_indirect_funds,
                    $event->attendees_count,
                    $event->charity_notes,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'Total', '', '', '',
                $events->sum('charity_direct_funds'),
                $events->sum('charity_indirect_funds'),
                $events->sum(fn ($e) => $e->charity_direct_funds + $e->charity_indirect_funds),
                $events->sum('attendees_count'),
                '',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
