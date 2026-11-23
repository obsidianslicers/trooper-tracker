<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Notices\PageData\NoticeWidgetPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the notice widget via an HTMX request.
 * This controller determines the number of notices visible to the authenticated user
 * and fetches the specific notice if only one is available.
 */
class ShowNoticeHtmxController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = NoticeWidgetPageData::call($request);

        return view('widgets.notice', $data);
    }
}
