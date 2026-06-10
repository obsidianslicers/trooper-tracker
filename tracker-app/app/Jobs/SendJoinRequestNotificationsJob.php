<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\JoinRequest;
use App\Notifications\Admin\JoinRequestSubmittedNotification;
use App\Policies\TrooperJoinRequestPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends join request notifications to admins and relevant moderators.
 *
 * Mirrors the pattern of SendTrooperRegisteredNotificationsJob: all admins
 * are notified, and moderators receive the notification only if they have
 * authority over the requested organization.
 */
class SendJoinRequestNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  JoinRequest  $join_request  The newly submitted pending JoinRequest
     */
    public function __construct(private readonly JoinRequest $join_request) {}

    public function handle(MagicBus $bus): void
    {
        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            $admin->notify(new JoinRequestSubmittedNotification($this->join_request));
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));

        $policy = new TrooperJoinRequestPolicy;

        foreach ($moderators as $moderator)
        {
            if ($policy->moderate($moderator, $this->join_request))
            {
                $moderator->notify(new JoinRequestSubmittedNotification($this->join_request));
            }
        }
    }
}
