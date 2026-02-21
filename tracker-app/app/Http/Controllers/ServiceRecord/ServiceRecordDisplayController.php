<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecord;

use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles the display of the main trooper service record.
 *
 * This controller gathers various statistics for a trooper, such as troop
 * counts by organization and costume, and displays them.
 */
class ServiceRecordDisplayController extends MagicBusController
{
    /**
     * Handle the incoming request to display the dashboard page for a trooper
     *
     * Fetches all relevant statistics for a given trooper (or the authenticated user)
     * and displays them on the main dashboard view. Redirects if the trooper is not found.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered dashboard page view
     */
    public function __invoke(Request $request): View
    {
        $trooper_id = (int) $request->get('trooper_id', Auth::user()->id);

        if ($trooper_id == Auth::user()->id)
        {
            $this->crumbs->addRoute('Profile', 'account.profile');
        }

        $service_record_query = new GetTrooperServiceRecordQuery($trooper_id);

        $data = $this->bus->send($service_record_query);

        return view('pages.service-record.display', $data);
    }
}
