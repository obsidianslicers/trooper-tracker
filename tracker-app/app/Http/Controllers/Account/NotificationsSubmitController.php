<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\NotificationRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\FlashMessageService;
use App\Services\Troopers\AssignTrooperNotificationsCommand;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the submission of the authenticated trooper's notification settings form.
 *
 * This controller processes updates to:
 * - Global notification frequency (NEVER, INSTANT, DAILY)
 * - Per-organization notification preferences (can_notify flags)
 *
 * The controller follows the ADR pattern:
 * - Validates input via NotificationRequest
 * - Updates trooper's notification_frequency
 * - Delegates per-organization updates to AssignTrooperNotificationsCommand
 * - Returns redirect with success flash message
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
     * Updates the authenticated trooper's notification preferences:
     * 1. Updates the trooper's global notification_frequency
     * 2. Delegates per-organization can_notify updates to AssignTrooperNotificationsCommand
     * 3. Flashes a success message
     * 4. Redirects back to the notification settings page
     *
     * @param NotificationRequest $request Validated request containing notification_frequency and organizations data
     * @param AssignTrooperNotificationsCommand $assign_notifications Service to update per-organization notification preferences
     * @return RedirectResponse Redirect to account.notifications route with success message
     */
    public function __invoke(
        NotificationRequest $request,
        AssignTrooperNotificationsCommand $assign_notifications): RedirectResponse
    {
        $trooper = $request->user();

        $trooper->notification_frequency = $request->validated('notification_frequency');
        $trooper->save();

        $assign_notifications($trooper, $request->validated('organizations', []));

        $this->flash->updated($trooper);

        return redirect()->route('account.notifications');
    }
}