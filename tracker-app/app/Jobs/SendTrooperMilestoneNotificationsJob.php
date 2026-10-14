<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperMilestoneNotification;
use App\Policies\TrooperPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sends a daily milestone roundup to admins and moderators in scope.
 *
 * Only notifies recipients who have opted in via the Squads / Clubs selection
 * in their notification settings (should_notify = true for at least one of the
 * trooper's member organizations). Moderators are additionally filtered to those
 * with authority over the trooper.
 */
class SendTrooperMilestoneNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(MagicBus $bus): void
    {
        $milestone_types = collect(AchievementType::cases())
            ->filter(fn (AchievementType $type): bool => $type->isMilestone())
            ->pluck('value');

        $achievements = TrooperAchievement::query()
            ->whereNull(TrooperAchievement::NOTIFICATION_SENT_AT)
            ->whereIn(TrooperAchievement::TYPE, $milestone_types)
            ->with([
                'organization',
                'trooper.trooper_assignments' => function ($query)
                {
                    $query->where(TrooperAssignment::IS_MEMBER, true)
                        ->with('organization');
                },
            ])
            ->orderBy(TrooperAchievement::ACHIEVEMENT_DATE)
            ->get();

        if ($achievements->isEmpty())
        {
            return;
        }

        // Notifications queue after commit, so the marker and the fan-out are
        // atomic: a failure rolls back the marker with nobody notified, and a
        // retry re-runs cleanly instead of excluding these achievements forever.
        DB::transaction(function () use ($bus, $achievements): void
        {
            TrooperAchievement::query()
                ->whereKey($achievements->modelKeys())
                ->update([TrooperAchievement::NOTIFICATION_SENT_AT => now()]);

            $this->notifyRecipients($bus, $achievements);
        });
    }

    /**
     * @param  Collection<int, TrooperAchievement>  $achievements
     */
    private function notifyRecipients(MagicBus $bus, Collection $achievements): void
    {
        /** @var Collection<int, Collection<int, TrooperAchievement>> $roundups */
        $roundups = collect();

        $admins = $bus->send(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        foreach ($admins as $admin)
        {
            $roundups->put($admin->id, $this->achievementsForRecipient($admin, $achievements));
        }

        $moderators = $bus->send(new GetTroopersByRoleQuery(MembershipRole::MODERATOR));
        $policy = new TrooperPolicy;

        foreach ($moderators as $moderator)
        {
            $eligible = $achievements->filter(function (TrooperAchievement $achievement) use ($moderator, $policy)
            {
                return $policy->moderate($moderator, $achievement->trooper)
                    && $this->recipientSubscribedToTrooper($moderator, $achievement);
            })->values();

            if ($eligible->isNotEmpty())
            {
                $roundups->put($moderator->id, ($roundups->get($moderator->id, collect()))
                    ->merge($eligible)->unique('id')->values());
            }
        }

        $recipients = $admins->merge($moderators)->keyBy('id');

        foreach ($roundups->filter->isNotEmpty() as $trooper_id => $recipient_achievements)
        {
            $recipients->get($trooper_id)?->notify(
                new TrooperMilestoneNotification($recipient_achievements),
            );
        }
    }

    private function achievementsForRecipient(Trooper $recipient, Collection $achievements): Collection
    {
        return $achievements
            ->filter(fn (TrooperAchievement $achievement): bool => $this->recipientSubscribedToTrooper($recipient, $achievement))
            ->values();
    }

    private function recipientSubscribedToTrooper(Trooper $recipient, TrooperAchievement $achievement): bool
    {
        $trooper_org_ids = $achievement->trooper->trooper_assignments
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->all();

        if (empty($trooper_org_ids))
        {
            return false;
        }

        return $recipient->trooper_assignments()
            ->where(TrooperAssignment::SHOULD_NOTIFY, true)
            ->whereIn(TrooperAssignment::ORGANIZATION_ID, $trooper_org_ids)
            ->exists();
    }
}
