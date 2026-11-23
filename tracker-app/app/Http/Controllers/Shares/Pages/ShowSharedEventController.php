<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\SharedEventPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the event sharing page.
 *
 * This controller is responsible for rendering the event sharing view
 * that allows users to share event information via various channels
 * such as social media, email, or direct links.
 */
class ShowSharedEventController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = SharedEventPageData::call($request);

        return view('pages.shares.event', $data);
    }
}
