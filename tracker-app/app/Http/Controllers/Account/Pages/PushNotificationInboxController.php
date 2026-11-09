<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Account\Queries\GetTrooperNotifications;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Display the push notifications inbox for the authenticated trooper.
 *
 * Retrieves all push notifications for the trooper and renders them
 * in the notifications page.
 */
class PushNotificationInboxController extends PageController
{
    /**
     * Display the push notifications inbox.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered push notifications page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $notifications = GetTrooperNotifications::call(trooper: $trooper);

        return view('pages.account.push-notifications', compact('notifications'));
    }
}
