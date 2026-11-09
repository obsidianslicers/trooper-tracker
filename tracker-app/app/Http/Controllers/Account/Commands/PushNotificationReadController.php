<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\Commands\Notifications\MarkPushNotificationAsRead;
use App\Models\TrooperNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mark a push notification as read and redirect to its URL.
 *
 * When trooper clicks a notification, this marks it as read in the database
 * and redirects to the URL the notification contains.
 */
class PushNotificationReadController extends Controller
{
    /**
     * Mark a notification as read and redirect to its URL.
     *
     * Verifies the notification belongs to the authenticated trooper,
     * marks it as read, then redirects to the notification's URL.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  TrooperNotification  $notification  The notification to mark as read
     * @return RedirectResponse Redirect to the notification's URL
     */
    public function __invoke(Request $request, TrooperNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        MarkPushNotificationAsRead::call(notification: $notification);

        return redirect($notification->url);
    }
}
