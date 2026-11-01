<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Notices\Queries\GetUnreadNotices;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the notices/announcements page for the authenticated trooper.
 *
 * This controller retrieves notices visible to the trooper based on their
 * organization memberships and displays unread notices ordered by start date.
 */
class NoticesController extends Controller
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

        $notices = GetUnreadNotices::call(trooper: $trooper);

        $data = compact('notices');

        return view('pages.account.notices', $data);
    }
}
