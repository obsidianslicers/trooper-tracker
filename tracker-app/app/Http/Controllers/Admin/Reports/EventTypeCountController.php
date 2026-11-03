<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Messages\Reports\Queries\GetEventTypeCount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying event type count report.
 *
 * Shows event statistics grouped by type with counts and
 * trooper participation metrics for each type.
 */
class EventTypeCountController extends BaseReportsController
{
    /**
     * Display the event type count report
     *
     * Retrieves event statistics grouped by type (Regular, Charity, etc.)
     * with counts and trooper participation metrics for events moderated
     * by the authenticated trooper.
     *
     * @param  Request  $request  The HTTP request
     * @return View The event type count report view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $lookback = 30;

        $event_types = GetEventTypeCount::call(moderator: $trooper, lookback: $lookback);

        $data = compact('event_types', 'lookback');

        return view('pages.admin.reports.event-type-count', $data);
    }
}
