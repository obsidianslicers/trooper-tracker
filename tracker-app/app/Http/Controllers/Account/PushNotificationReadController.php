<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Models\TrooperNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationReadController
{
    public function __invoke(Request $request, TrooperNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        $notification->markAsRead();

        return redirect($notification->url);
    }
}
