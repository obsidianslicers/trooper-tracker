<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Account\PageData\PushNotificationsPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
     * @return View The rendered push notifications page
     */
    public function __invoke(Request $request): View
    {
        $data = PushNotificationsPageData::call($request);

        return view('pages.account.push-notifications', $data);
    }
}
