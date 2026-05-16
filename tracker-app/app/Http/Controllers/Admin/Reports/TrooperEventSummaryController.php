<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetTrooperEventSummaryQuery;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrooperEventSummaryController extends BaseReportsController
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

        $active_only = (bool) $request->input('active_only', false);

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

        $sort = $request->input('sort', 'event_shifts_count');
        $dir = $request->input('dir', 'desc');

        if ($request->input('format') === 'csv')
        {
            $all = $this->bus->send(new GetTrooperEventSummaryQuery($trooper, $date_start, $date_end, $active_only, PHP_INT_MAX, $organization, $sort, $dir, $accessible_org_ids));

            return $this->streamCsv($all, $date_start, $date_end, $active_only, $organization?->name);
        }

        $trooper_events = $this->bus->send(new GetTrooperEventSummaryQuery($trooper, $date_start, $date_end, $active_only, 50, $organization, $sort, $dir, $accessible_org_ids));

        $data = compact('trooper_events', 'date_start', 'date_end', 'active_only', 'organizations', 'organization_id', 'sort', 'dir');

        return view('pages.admin.reports.trooper-event-summary', $data);
    }

    private function streamCsv(LengthAwarePaginator $trooper_events, ?Carbon $date_start, ?Carbon $date_end, bool $active_only, ?string $organization_name): StreamedResponse
    {
        $filename = 'trooper-event-summary-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($trooper_events, $date_start, $date_end, $active_only, $organization_name) {
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
            if ($active_only)
            {
                $meta[] = 'Active Members Only';
            }
            if (!empty($meta))
            {
                fputcsv($handle, $meta);
            }

            fputcsv($handle, ['Trooper', 'Unique Events', 'Total Shifts']);

            foreach ($trooper_events as $trooper_event)
            {
                fputcsv($handle, [
                    $trooper_event->display_name,
                    $trooper_event->events_count,
                    $trooper_event->event_shifts_count,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'Total',
                $trooper_events->sum('events_count'),
                $trooper_events->sum('event_shifts_count'),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
