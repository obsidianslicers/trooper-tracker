<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Models\PushNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationReadController
{
    public function __invoke(Request $request, PushNotification $push_notification): RedirectResponse
    {
        abort_unless($push_notification->trooper_id === $request->user()->id, 403);

        $push_notification->markRead();

        return redirect($push_notification->url);
    }
}
