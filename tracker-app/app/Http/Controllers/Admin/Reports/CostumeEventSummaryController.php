<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Features\Reports\Queries\GetCostumeEventSummaryQuery;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CostumeEventSummaryController extends BaseReportsController
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

        $sort = $request->input('sort', 'uses_count');
        $dir = $request->input('dir', 'desc');

        if ($request->input('format') === 'csv')
        {
            $all = $this->bus->send(new GetCostumeEventSummaryQuery($trooper, $date_start, $date_end, PHP_INT_MAX, $organization, $sort, $dir, $accessible_org_ids));

            return $this->streamCsv($all, $date_start, $date_end, $organization?->name);
        }

        $costume_events = $this->bus->send(new GetCostumeEventSummaryQuery($trooper, $date_start, $date_end, 50, $organization, $sort, $dir, $accessible_org_ids));

        $data = compact('costume_events', 'date_start', 'date_end', 'organizations', 'organization_id', 'sort', 'dir');

        return view('pages.admin.reports.costume-event-summary', $data);
    }

    private function streamCsv(LengthAwarePaginator $costume_events, ?Carbon $date_start, ?Carbon $date_end, ?string $organization_name): StreamedResponse
    {
        $filename = 'costume-event-summary-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($costume_events, $date_start, $date_end, $organization_name) {
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
            if (!empty($meta))
            {
                fputcsv($handle, $meta);
            }

            fputcsv($handle, ['Costume', 'Unique Events', 'Total Uses']);

            foreach ($costume_events as $costume_event)
            {
                fputcsv($handle, [
                    $costume_event->name,
                    $costume_event->events_count,
                    $costume_event->uses_count,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'Total',
                $costume_events->sum('events_count'),
                $costume_events->sum('uses_count'),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
