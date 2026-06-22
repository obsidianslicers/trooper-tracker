<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperMilestoneNotification;
use App\Policies\TrooperPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends milestone achievement notifications to admins and moderators in scope.
 *
 * Only notifies recipients who have opted in via the Squads / Clubs selection
 * in their notification settings (should_notify = true for at least one of the
 * trooper's member organizations). Moderators are additionally filtered to those
 * with authority over the trooper.
 */
class SendTrooperMilestoneNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TrooperAchievement $achievement) {}

    public function handle(MagicBus $bus): void
    {
        $trooper = $this->achievement->trooper;

        $trooper_org_ids = $trooper->trooper_assignments()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->all();

        if (empty($trooper_org_ids))
        {
            return;
        }

        $notification = new TrooperMilestoneNotification($this->achievement);

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            if ($admin->trooper_assignments()
                ->where(TrooperAssignment::SHOULD_NOTIFY, true)
                ->whereIn(TrooperAssignment::ORGANIZATION_ID, $trooper_org_ids)
                ->exists())
            {
                $admin->notify($notification);
            }
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));
        $policy = new TrooperPolicy;

        foreach ($moderators as $moderator)
        {
            if ($policy->moderate($moderator, $trooper)
                && $moderator->trooper_assignments()
                    ->where(TrooperAssignment::SHOULD_NOTIFY, true)
                    ->whereIn(TrooperAssignment::ORGANIZATION_ID, $trooper_org_ids)
                    ->exists())
            {
                $moderator->notify($notification);
            }
        }
    }
}
