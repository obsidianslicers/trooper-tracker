<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets;

use App\Http\Controllers\Controller;
use App\Messages\Notices\Queries\GetFirstUnreadNotice;
use App\Messages\Notices\Queries\GetUnreadNoticeCount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the notice widget via an HTMX request.
 * This controller determines the number of notices visible to the authenticated user
 * and fetches the specific notice if only one is available.
 */
class NoticeDisplayHtmxController extends Controller
{
    /**
     * Handle the incoming request to display the notice widget
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered notice widget view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $count = GetUnreadNoticeCount::call(trooper: $trooper);

        $notice = GetFirstUnreadNotice::call(trooper: $trooper);

        $data = compact('count', 'notice');

        return view('widgets.notice', $data);
    }
}
