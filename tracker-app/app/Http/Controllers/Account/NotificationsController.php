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
 * Displays the authenticated trooper's notification settings page.
 *
 * This controller follows the ADR pattern as an Action that:
 * - Retrieves the authenticated trooper's notification preferences
 * - Loads all organizations with hierarchical structure (Org → Region → Unit)
 * - Marks which organizations have notifications enabled for the trooper
 * - Renders the notification settings view for trooper modification
 */
class NotificationsController extends Controller
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
     *    - Selected organizations marked with can_notify = true
     *
     * @param Request $request The incoming HTTP request containing the authenticated trooper
     * @return View The rendered notification settings view (pages.account.notifications)
     */
    public function __invoke(Request $request): View
    {
        $data = $this->getTrooperNotifications($request->user());

        return view('pages.account.notifications', $data);
    }

    /**
     * Gathers all notification-related data for a given trooper.
     *
     * Fetches the complete hierarchical organization structure and marks
     * which organizations have notifications enabled for the trooper based
     * on their TrooperAssignment records where can_notify = true.
     *
     * The method iterates through three levels:
     * - Organizations (top-level clubs/garrisons)
     * - Regions (child organizations)
     * - Units (grandchild organizations)
     *
     * Each organization object receives a 'selected' property indicating
     * whether the trooper has notifications enabled for that organization.
     *
     * @param Trooper $trooper The trooper for whom to fetch notification data
     * @return array<string, mixed> Array containing:
     *                              - 'organizations' => Collection of organizations with selected flags
     *                              - 'notification_frequency' => Trooper's global notification preference
     */
    private function getTrooperNotifications(Trooper $trooper): array
    {
        $organizations = Organization::fullyLoaded()->get();

        $trooper_assignments = $trooper->trooper_assignments()
            ->where(TrooperAssignment::CAN_NOTIFY, true)
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
            'notification_frequency' => $trooper->notification_frequency,
        ];

        return $data;
    }
}
