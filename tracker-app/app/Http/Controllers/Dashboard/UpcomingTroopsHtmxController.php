<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles HTMX requests to display a trooper's upcoming troops on the dashboard.
 *
 * This controller fetches future event sign-ups for a given trooper and
 * returns a view partial containing the list, sorted by the soonest event first.
 */
class UpcomingTroopsHtmxController extends Controller
{
    /**
     * Handle the incoming request to display the upcoming troops partial.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered view partial for upcoming troops.
     */
    public function __invoke(Request $request): View
    {
        $trooper_id = (int) $request->get('trooper_id', Auth::user()->id);

        $upcoming_shifts = EventShift::with('event.organization')
            ->byTrooper($trooper_id, false)
            ->orderByDesc(EventShift::SHIFT_ENDS_AT)
            ->get();

        $data = compact('upcoming_shifts');

        return view('pages.dashboard.upcoming-troops', $data);
    }
}
