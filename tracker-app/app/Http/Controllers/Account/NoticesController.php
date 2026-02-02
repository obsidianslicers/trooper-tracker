<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Notices\Queries\GetTrooperNoticesQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the notices/announcements page for the authenticated trooper.
 *
 * This controller retrieves notices visible to the trooper based on their
 * organization memberships and displays unread notices ordered by start date.
 */
class NoticesController extends MagicBusController
{
    /**
     * Display the notices page for the authenticated trooper.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered notices page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $notices_query = new GetTrooperNoticesQuery($trooper);

        $notices = $this->bus->send($notices_query);

        $data = compact('notices');

        return view('pages.account.notices', $data);
    }
}
