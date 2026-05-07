<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PushNotificationInboxController
{
    public function __invoke(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->get();

        return view('pages.account.push-notifications', compact('notifications'));
    }
}
