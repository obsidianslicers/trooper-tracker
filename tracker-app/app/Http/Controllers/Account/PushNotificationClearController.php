<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Models\PushNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationClearController
{
    public function __invoke(Request $request): RedirectResponse
    {
        PushNotification::where(PushNotification::TROOPER_ID, $request->user()->id)->delete();

        return redirect()->route('account.push-notifications');
    }
}
