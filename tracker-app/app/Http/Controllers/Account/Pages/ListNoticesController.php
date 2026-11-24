<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Account\PageData\NoticesPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the notices/announcements page for the authenticated trooper.
 *
 * This controller retrieves notices visible to the trooper based on their
 * organization memberships and displays unread notices ordered by start date.
 */
class ListNoticesController extends PageController
{
    /**
     * Display the notices page for the authenticated trooper.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse The rendered notices page
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $data = NoticesPageData::call($request);

        return Inertia::render('account/Notices', $data);
    }
}
