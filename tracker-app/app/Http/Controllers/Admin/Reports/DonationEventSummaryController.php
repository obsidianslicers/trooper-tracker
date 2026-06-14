<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetDonationEventSummaryQuery;
use App\Models\Organization;
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
        $dir = $request->input('dir', 'desc');

        $organizations = Organization::moderatedBy($trooper)
            ->orderBy(Organization::NAME)
            ->get(['id', 'name', 'node_path']);

        $organization_id = $request->integer('organization_id') ?: null;
        $organization = $organization_id
            ? $organizations->firstWhere('id', $organization_id)
            : null;

        $accessible_org_ids = $organizations
            ->map(fn ($org) => (int) explode(Organization::NODE_PATH_SEP, $org->node_path)[0])
            ->unique()
            ->values()
            ->all();

        if ($request->input('format') === 'csv')
        {
            $all = $this->bus->send(new GetDonationEventSummaryQuery($trooper, $date_start, $date_end, $charity_only, PHP_INT_MAX, $sort, $dir, $organization, $accessible_org_ids));

            return $this->streamCsv($all, $date_start, $date_end, $charity_only, $organization?->name);
        }

        $events = $this->bus->send(new GetDonationEventSummaryQuery($trooper, $date_start, $date_end, $charity_only, 50, $sort, $dir, $organization, $accessible_org_ids));

        $data = compact('events', 'date_start', 'date_end', 'charity_only', 'sort', 'dir', 'organizations', 'organization_id');

        return view('pages.admin.reports.donation-event-summary', $data);
    }

    private function streamCsv(LengthAwarePaginator $events, ?Carbon $date_start, ?Carbon $date_end, bool $charity_only, ?string $organization_name = null): StreamedResponse
    {
        $filename = 'donation-event-summary-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($events, $date_start, $date_end, $charity_only, $organization_name) {
            $handle = fopen('php://output', 'w');

            $meta = [];
            if ($organization_name)
            {
                $meta[] = 'Club: '.$organization_name;
            }
            if ($date_start)
            {
                $meta[] = 'From: '.$date_start->format('Y-m-d');
            }
            if ($date_end)
            {
                $meta[] = 'To: '.$date_end->format('Y-m-d');
            }
            if ($charity_only)
            {
                $meta[] = 'Charity Data Only';
            }
            if (!empty($meta))
            {
                fputcsv($handle, $meta);
            }

            fputcsv($handle, ['Event', 'Date', 'Club', 'Shift', 'Charity', 'Direct Funds', 'Indirect Funds', 'Total Funds', 'Hours', 'Attendees', 'Trooper Hours', 'Notes']);

            $total_direct = 0;
            $total_indirect = 0;
            $total_hours = 0;
            $total_attended = 0;
            $total_trooper_hours = 0;

            foreach ($events as $event)
            {
                foreach ($event->event_shifts as $shift)
                {
                    $direct = $shift->charity_direct_funds;
                    $indirect = $shift->charity_indirect_funds;
                    $hours = $shift->effective_charity_hours;
                    $attended = $shift->attendees_count;
                    $trooper_hours = $hours * $attended;

                    fputcsv($handle, [
                        $event->name,
                        $event->event_start?->format('Y-m-d'),
                        $event->organization?->name,
                        $shift->compact_time_display,
                        $shift->charity_name,
                        $direct,
                        $indirect,
                        $direct + $indirect,
                        $hours,
                        $attended,
                        $trooper_hours,
                        $shift->charity_notes,
                    ]);

                    $total_direct        += $direct;
                    $total_indirect      += $indirect;
                    $total_hours         += $hours;
                    $total_attended      += $attended;
                    $total_trooper_hours += $trooper_hours;
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'Total', '', '', '', '',
                $total_direct,
                $total_indirect,
                $total_direct + $total_indirect,
                $total_hours,
                $total_attended,
                $total_trooper_hours,
                '',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
