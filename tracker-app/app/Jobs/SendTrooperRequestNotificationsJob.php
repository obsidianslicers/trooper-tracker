<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\TrooperRequest;
use App\Notifications\Admin\TrooperRequestSubmittedNotification;
use App\Policies\TrooperRequestPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends join request notifications to admins and relevant moderators.
 *
 * Mirrors the pattern of SendTrooperRegisteredNotificationsJob: all admins
 * are notified, and moderators receive the notification only if they have
 * authority over the requested organization.
 */
class SendTrooperRequestNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  TrooperRequest  $trooper_request  The newly submitted pending TrooperRequest
     */
    public function __construct(private readonly TrooperRequest $trooper_request) {}

    public function handle(): void
    {
        $admins = GetTroopersByRole::call(membership_role: MembershipRole::ADMINISTRATOR);

        foreach ($admins as $admin)
        {
            $admin->notify(new TrooperRequestSubmittedNotification($this->trooper_request));
        }

        $moderators = GetTroopersByRole::call(membership_role: MembershipRole::MODERATOR);

        $policy = new TrooperRequestPolicy;

        foreach ($moderators as $moderator)
        {
            if ($policy->moderate($moderator, $this->trooper_request))
            {
                $moderator->notify(new TrooperRequestSubmittedNotification($this->trooper_request));
            }
        }
    }
}
