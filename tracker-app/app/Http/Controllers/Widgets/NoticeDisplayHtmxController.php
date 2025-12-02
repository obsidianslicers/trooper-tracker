<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles displaying the notice widget via an HTMX request.
 * This controller determines the number of notices visible to the authenticated user
 * and fetches the specific notice if only one is available.
 */
class NoticeDisplayHtmxController extends Controller
{
    /**
     * Handle the incoming request to display the notice widget.
     *
     * @return View The rendered notice widget view.
     */
    public function __invoke(): View
    {
        $trooper = Auth::user();

        $notice = null;

        $count = Notice::visibleTo($trooper, true)->count();

        if ($count == 1)
        {
            $notice = Notice::visibleTo($trooper, true)->first();
        }

        $data = [
            'count' => $count,
            'notice' => $notice
        ];

        return view('widgets.notice', $data);
    }
}
