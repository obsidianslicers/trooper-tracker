<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets;

use App\Features\Notices\Queries\GetTrooperNoticeForDisplayQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the notice widget via an HTMX request.
 * This controller determines the number of notices visible to the authenticated user
 * and fetches the specific notice if only one is available.
 */
class NoticeDisplayHtmxController extends MagicBusController
{
    /**
     * Handle the incoming request to display the notice widget.
     *
     * @return View The rendered notice widget view.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $query = new GetTrooperNoticeForDisplayQuery($trooper, true);

        ['count' => $count, 'notice' => $notice] = $this->bus->send($query);

        $data = compact('count', 'notice');

        return view('widgets.notice', $data);
    }
}
