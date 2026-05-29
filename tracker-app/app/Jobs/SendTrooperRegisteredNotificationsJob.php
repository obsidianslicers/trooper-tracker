<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\Trooper;
use App\Notifications\Admin\TrooperRegisteredNotification;
use App\Policies\TrooperPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends awaiting approval notifications to admins and moderators when a trooper registers.
 *
 * This job notifies all administrators and moderators when a new trooper completes
 * registration. Channel routing (email, push, in-app) and preference checks are
 * handled by TrooperRegisteredNotification::via().
 */
class SendTrooperRegisteredNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Trooper  $trooper  The newly registered trooper awaiting approval.
     */
    public function __construct(private readonly Trooper $trooper) {}

    public function handle(MagicBus $bus): void
    {
        event(new Registered($this->trooper));

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            $admin->notify(new TrooperRegisteredNotification);
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));

        $policy = new TrooperPolicy;

        foreach ($moderators as $moderator)
        {
            if ($policy->moderate($moderator, $this->trooper))
            {
                $moderator->notify(new TrooperRegisteredNotification);
            }
        }
    }
}
