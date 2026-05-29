<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Admin\Troopers\TrooperJoinRequestSubmitted;
use App\Models\TrooperOrganization;
use App\Policies\TrooperJoinRequestPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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
     * @param  TrooperOrganization  $join_request  The newly submitted pending TrooperOrganization
     */
    public function __construct(private readonly TrooperOrganization $join_request) {}

    public function handle(MagicBus $bus): void
    {
        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            if ($admin->emailAppearsValid() && $admin->wantsNotification('join_requests'))
            {
                Mail::to($admin->email)->queue(new TrooperJoinRequestSubmitted($this->join_request));
            }
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));

        $policy = new TrooperJoinRequestPolicy;

        foreach ($moderators as $moderator)
        {
            if ($moderator->emailAppearsValid()
                && $moderator->wantsNotification('join_requests')
                && $policy->moderate($moderator, $this->join_request))
            {
                Mail::to($moderator->email)->queue(new TrooperJoinRequestSubmitted($this->join_request));
            }
        }
    }
}
