<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetTrooperNotificationsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the authenticated trooper's notification settings page.
 *
 * This controller retrieves the trooper's notification preferences and
 * displays all organizations with current notification settings marked.
 */
class NotificationsController extends MagicBusController
{
    /**
     * Handle the incoming request to display the notification settings.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered notification settings page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $notifications_query = new GetTrooperNotificationsQuery($trooper);

        $organizations = $this->bus->send($notifications_query);

        $data = compact('organizations');

        $data['notification_frequency'] = $trooper->notification_frequency;

        return view('pages.account.notifications', $data);
    }
}
