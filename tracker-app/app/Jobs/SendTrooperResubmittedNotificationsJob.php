<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\Trooper;
use App\Notifications\Admin\TrooperResubmittedNotification;
use App\Policies\TrooperPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends resubmission notifications to admins and moderators when a denied trooper reapplies.
 *
 * Mirrors SendTrooperRegisteredNotificationsJob: all admins are notified,
 * moderators only if they have jurisdiction over the trooper.
 */
class SendTrooperResubmittedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Trooper $trooper) {}

    public function handle(MagicBus $bus): void
    {
        $notification = new TrooperResubmittedNotification($this->trooper);

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            $admin->notify($notification);
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));

        $policy = new TrooperPolicy;

        foreach ($moderators as $moderator)
        {
            if ($policy->moderate($moderator, $this->trooper))
            {
                $moderator->notify($notification);
            }
        }
    }
}
