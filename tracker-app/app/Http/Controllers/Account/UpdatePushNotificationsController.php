<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePushNotificationsRequest;
use App\Messages\Troopers\Commands\UpdateTrooperPushNotifications;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for updating the authenticated trooper's push notifications.
 *
 * This controller validates push notifications data via UpdatePushNotificationsRequest, dispatches
 * UpdateTrooperPushNotifications to persist changes, and redirects back to the update profile page.
 */
class UpdatePushNotificationsController extends Controller
{
    /**
     * Handle the incoming request to update the trooper's push notifications.
     *
     * @param  UpdatePushNotificationsRequest  $request  The validated push notifications update request
     * @return InertiaResponse|SymfonyResponse Redirect to the update push notifications page with success message
     */
    public function __invoke(UpdatePushNotificationsRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        UpdateTrooperPushNotifications::call(
            trooper: $trooper,
            push_notifications_enabled: $request->validated('push_notifications_enabled'),
        );

        return Inertia::render('account/Index');
    }
}
