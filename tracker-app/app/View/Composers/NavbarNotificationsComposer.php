<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Messages\Account\Queries\GetTrooperNotifications;
use App\Messages\Events\Queries\GetTroopsToConfirm;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Everything under the bell in the navbar: the troops the trooper hasn't said whether they made
 * it to, pinned at the top, then the most recent notifications (the notifications page has the
 * rest), and the badge counting both. Runs only when the bell is drawn, so pages without the
 * navbar (JSON, partial reloads) don't pay for the queries.
 *
 * The "did you make it?" reminders stay in the notification log, for the notifications page and
 * the mobile app, but the bell leaves them out: its pinned section already asks.
 */
class NavbarNotificationsComposer
{
    public const PREVIEW_COUNT = 10;

    public const TROOPS_TO_CONFIRM_PREVIEW_COUNT = 5;

    public function compose(View $view): void
    {
        if (!Auth::check())
        {
            $view->with([
                'navbar_notifications' => collect(),
                'navbar_troops_to_confirm' => collect(),
                'navbar_troops_to_confirm_count' => 0,
                'navbar_badge_count' => 0,
            ]);

            return;
        }

        $trooper = Auth::user();
        $troops_to_confirm = GetTroopsToConfirm::call(trooper: $trooper);

        $unread_count = $trooper->unreadNotifications()
            ->where(TrooperNotification::TYPE, '!=', EventShiftCompletedNotification::class)
            ->count();

        $view->with([
            'navbar_notifications' => GetTrooperNotifications::call(
                trooper: $trooper,
                limit: self::PREVIEW_COUNT,
                except_type: EventShiftCompletedNotification::class,
            ),
            'navbar_troops_to_confirm' => $troops_to_confirm->take(self::TROOPS_TO_CONFIRM_PREVIEW_COUNT),
            'navbar_troops_to_confirm_count' => $troops_to_confirm->count(),
            'navbar_badge_count' => $unread_count + $troops_to_confirm->count(),
        ]);
    }
}
