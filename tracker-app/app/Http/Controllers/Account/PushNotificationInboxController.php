<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Models\PushNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PushNotificationInboxController
{
    public function __invoke(Request $request): View
    {
        $notifications = PushNotification::where(PushNotification::TROOPER_ID, $request->user()->id)
            ->latest()
            ->get();

        return view('pages.account.push-notifications', compact('notifications'));
    }
}
