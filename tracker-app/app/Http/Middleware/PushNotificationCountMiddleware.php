<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class PushNotificationCountMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $count = Auth::check()
            ? Auth::user()->unreadNotifications()->count()
            : 0;

        View::share('pushNotificationUnreadCount', $count);

        return $next($request);
    }
}
