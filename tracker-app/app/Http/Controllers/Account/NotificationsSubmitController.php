<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\NotificationRequest;
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
class NotificationsSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update notification settings.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Dispatches UpdateTrooperCommand to update global notification_frequency
     * 3. Dispatches UpdateTrooperNotificationsCommand to update per-organization can_notify flags
     * 4. Flashes success message via FlashMessageService
     * 5. Redirects to account.notifications route
     *
     * @param NotificationRequest $request Validated request containing notification_frequency and organizations data
     * @return RedirectResponse Redirect to account.notifications route with success message
     */
    public function __invoke(NotificationRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $update_cmd = new UpdateTrooperCommand($trooper, $request->validated());

        $this->bus->send($update_cmd);

        $update_notifications_cmd = new UpdateTrooperNotificationsCommand($trooper, $request->validated('organizations', []));

        $this->bus->send($update_notifications_cmd);

        $this->flash->updated($trooper);

        return redirect()->route('account.notifications');
    }
}