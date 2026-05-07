<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushNotificationClearController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return redirect()->route('account.push-notifications');
    }
}
