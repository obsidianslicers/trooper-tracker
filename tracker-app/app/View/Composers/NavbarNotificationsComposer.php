<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Messages\Account\Queries\GetTrooperNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The most recent notifications, previewed under the bell in the navbar; the notifications
 * page has the rest. Runs only when the bell is drawn, so pages without the navbar (HTMX
 * fragments, JSON) don't pay for the query.
 */
class NavbarNotificationsComposer
{
    public const PREVIEW_COUNT = 10;

    public function compose(View $view): void
    {
        $view->with('navbar_notifications', Auth::check()
            ? GetTrooperNotifications::call(trooper: Auth::user(), limit: self::PREVIEW_COUNT)
            : collect());
    }
}
