<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Account\PageData\PushNotificationsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Display the push notifications inbox for the authenticated trooper.
 *
 * Lists the troops waiting for "did you make it?", with both answers, then the
 * trooper's push notifications: the same as the bell, without its limits.
 */
class ListPushNotificationsController extends PageController
{
    /**
     * Display the push notifications inbox.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse The rendered push notifications page
     */
    public function __invoke(Request $request): InertiaResponse
    {
        $data = PushNotificationsPageData::call($request);

        return Inertia::render('account/PushNotifications', $data);
    }
}
