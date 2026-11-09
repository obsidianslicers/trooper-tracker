<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\Commands\Notifications\ClearPushNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Clear all push notifications for the authenticated trooper.
 *
 * Deletes all notification records for the trooper and redirects back
 * to the push notifications inbox page.
 */
class PushNotificationClearController extends Controller
{
    /**
     * Clear all push notifications for the trooper.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return RedirectResponse Redirect to push notifications page
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        ClearPushNotifications::call(trooper: $trooper);

        return redirect()->route('account.push-notifications');
    }
}
