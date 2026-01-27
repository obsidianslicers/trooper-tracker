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
 * This controller follows the ADR pattern as an Action that:
 * - Retrieves the authenticated trooper's notification preferences
 * - Loads all organizations with hierarchical structure (Org → Region → Unit)
 * - Marks which organizations have notifications enabled for the trooper
 * - Renders the notification settings view for trooper modification
 */
class NotificationsController extends MagicBusController
{
    /**
     * Handle the incoming request to display the notification settings.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Gathers notification data via getTrooperNotifications()
     * 3. Renders the notification settings page with:
     *    - Hierarchical organization list (Org → Region → Unit)
     *    - Current notification_frequency setting
     *    - Selected organizations marked with should_notify = true
     *
     * @param Request $request The incoming HTTP request containing the authenticated trooper
     * @return View The rendered notification settings view (pages.account.notifications)
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
