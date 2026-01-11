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
     * @param NotificationRequest $request The incoming HTTP request.
     * @return RedirectResponse A redirect response to the notifications settings page.
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