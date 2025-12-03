<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Services\FlashMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the submission of the authenticated user's notification settings form.
 */
class NotificationsSubmitController extends Controller
{
    /**
     * NotificationsSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for creating flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update notification settings.
     *
     * This method validates the incoming request, updates the authenticated user's
     * global and per-organization notification preferences, flashes a success
     * message, and redirects back to the notifications settings page.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse A redirect response to the notifications settings page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        $this->updateTrooperNotifications($trooper, $request->all());

        $this->flash->updated($trooper);

        return redirect()->route('account.notifications');
    }

    /**
     * Update the trooper's notification preferences in the database.
     *
     * This method processes the incoming form data to update the trooper's
     * global notification flags and their specific notification subscriptions
     * for each organization, region, and unit.
     *
     * @param Trooper $trooper The trooper to update.
     * @param array $data The raw data from the request.
     */
    private function updateTrooperNotifications(Trooper $trooper, array $data): void
    {
        $organizations = Organization::fullyLoaded()->get();

        $trooper->instant_notification = $data['instant_notification'] ?? false;
        $trooper->attendance_notification = $data['attendance_notification'] ?? false;
        $trooper->command_staff_notification = $data['command_staff_notification'] ?? false;

        $trooper->save();

        foreach ($organizations as $organization)
        {
            $notify = isset($data['organizations'][$organization->id]['notification']);

            $trooper->trooper_assignments()->updateOrCreate(
                [TrooperAssignment::ORGANIZATION_ID => $organization->id],
                [TrooperAssignment::NOTIFY => $notify]
            );

            foreach ($organization->organizations as $region)
            {
                $notify = isset($data['regions'][$region->id]['notification']);

                $trooper->trooper_assignments()->updateOrCreate(
                    [TrooperAssignment::ORGANIZATION_ID => $region->id],
                    [TrooperAssignment::NOTIFY => $notify]
                );

                foreach ($region->organizations as $unit)
                {
                    $notify = isset($data['units'][$unit->id]['notification']);

                    $trooper->trooper_assignments()->updateOrCreate(
                        [TrooperAssignment::ORGANIZATION_ID => $unit->id],
                        [TrooperAssignment::NOTIFY => $notify]
                    );
                }
            }
        }
    }
}