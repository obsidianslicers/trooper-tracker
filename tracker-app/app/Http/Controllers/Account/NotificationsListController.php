<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the authenticated user's notification settings page.
 */
class NotificationsListController extends Controller
{
    /**
     * Handle the incoming request to display the notification settings.
     *
     * This method retrieves the notification preferences and organizational
     * notification subscriptions for the currently authenticated user and
     * renders the corresponding view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered notification settings view.
     */
    public function __invoke(Request $request): View
    {
        $data = $this->getTrooperNotifications($request->user());

        return view('pages.account.notifications', $data);
    }

    /**
     * Gathers all notification-related data for a given trooper.
     *
     * This method fetches all organizations and cross-references them with the
     * trooper's notification assignments to determine which organizational
     * notifications are enabled. It also includes the trooper's global
     * notification preferences.
     *
     * @param Trooper $trooper The trooper for whom to fetch notification data.
     * @return array An array of data ready for the view.
     */
    private function getTrooperNotifications(Trooper $trooper): array
    {
        $organizations = Organization::fullyLoaded()->get();

        $trooper_assignments = $trooper->trooper_assignments()
            ->where(TrooperAssignment::NOTIFY, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->toArray();

        foreach ($organizations as $organization)
        {
            $organization->selected = in_array($organization->id, $trooper_assignments);

            foreach ($organization->organizations as $region)
            {
                $region->selected = in_array($region->id, $trooper_assignments);

                foreach ($region->organizations as $unit)
                {
                    $unit->selected = in_array($unit->id, $trooper_assignments);
                }
            }
        }

        $data = [
            'organizations' => $organizations,
            'instant_notification' => $trooper->instant_notification,
            'attendance_notification' => $trooper->attendance_notification,
            'command_staff_notification' => $trooper->command_staff_notification,
        ];

        return $data;
    }
}
