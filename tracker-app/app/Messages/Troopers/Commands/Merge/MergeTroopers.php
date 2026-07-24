<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * Merges all relationships of the source trooper into the target trooper.
 * This includes organizations, assignments, costumes, achievements, donations,
 * friends, requests, awards, events, uploads, watches, mission acknowledgments,
 * notifications, shares, mobile devices, model changes, notices, and OAuth logins.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTroopers extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function ()
        {
            /**
             * tt_trooper_organizations
             * tt_trooper_assignments
             * tt_trooper_costumes
             * tt_trooper_achievements
             * tt_trooper_donations
             * tt_trooper_friends
             * tt_trooper_requests
             * tt_award_troopers
             * tt_event_troopers
             * tt_event_upload_troopers
             * tt_event_uploads
             * tt_event_watches
             * tt_event_mission_acks
             * tt_event_notifications
             * tt_event_shares
             * tt_mobile_devices
             * tt_model_changes
             * tt_notice_troopers
             * tt_oauth_logins
             */
            $this->mergeRelationships();
            $this->mergeTrooperAccounts();
        });
    }

    private function mergeRelationships(): void
    {
        MergeTrooperOrganizations::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperAssignments::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperCostumes::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        // MergeTrooperAchievements::call(
        //     target_trooper: $this->target_trooper,
        //     source_trooper: $this->source_trooper,
        // );
        MergeTrooperDonations::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperFriends::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        // MergeTrooperRequests::call(
        //     target_trooper: $this->target_trooper,
        //     source_trooper: $this->source_trooper,
        // );
        MergeAwardTroopers::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventTroopers::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventUploadTroopers::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventUploads::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventWatches::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventMissionAcks::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventNotifications::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeEventShares::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeMobileDevices::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeModelChanges::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeNoticeTroopers::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeOauthLogins::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
    }

    private function mergeTrooperAccounts(): void
    {
        $this->target_trooper->phone = $this->target_trooper->phone ?? $this->source_trooper->phone;
        $this->target_trooper->setup_completed_at = $this->target_trooper->setup_completed_at ?? $this->source_trooper->setup_completed_at;
        $this->target_trooper->membership_status = $this->resolveMembershipStatus();
        $this->target_trooper->membership_role = $this->resolveMembershipRole();
        $this->target_trooper->notification_frequency = $this->resolveNotificationFrequency();
        $this->target_trooper->push_notifications_enabled = $this->target_trooper->push_notifications_enabled || $this->source_trooper->push_notifications_enabled;
        $this->target_trooper->notification_preferences = $this->mergeNotificationPreferences();
        $this->target_trooper->display_costume_id = $this->target_trooper->display_costume_id ?? $this->source_trooper->display_costume_id;
        $this->target_trooper->visitor_expires_at = $this->latestDateTime(
            $this->target_trooper->visitor_expires_at,
            $this->source_trooper->visitor_expires_at,
        );
        $this->target_trooper->visitor_notified_at = $this->latestDateTime(
            $this->target_trooper->visitor_notified_at,
            $this->source_trooper->visitor_notified_at,
        );

        $this->target_trooper->guardian_id = $this->resolveGuardianId();
        $this->target_trooper->date_of_birth = $this->target_trooper->date_of_birth ?? $this->source_trooper->date_of_birth;
        $this->target_trooper->save();

        $this->source_trooper->membership_status = MembershipStatus::MERGED;
        $this->source_trooper->visitor_expires_at = null;
        $this->source_trooper->visitor_notified_at = null;
        $this->source_trooper->deletion_requested_at = null;
        $this->source_trooper->save();
    }

    private function resolveMembershipStatus(): MembershipStatus
    {
        $rankings = [
            MembershipStatus::MERGED->value => 0,
            MembershipStatus::DEPARTED->value => 10,
            MembershipStatus::INACTIVE->value => 20,
            MembershipStatus::NONE->value => 30,
            MembershipStatus::DENIED->value => 40,
            MembershipStatus::PENDING->value => 50,
            MembershipStatus::RETIRED->value => 60,
            MembershipStatus::RESERVE->value => 70,
            MembershipStatus::ACTIVE->value => 80,
        ];

        $target_status = $this->target_trooper->membership_status;
        $source_status = $this->source_trooper->membership_status;

        return ($rankings[$source_status->value] ?? -1) > ($rankings[$target_status->value] ?? -1)
            ? $source_status
            : $target_status;
    }

    private function resolveMembershipRole(): MembershipRole
    {
        $rankings = [
            MembershipRole::VISITOR->value => 0,
            MembershipRole::HANDLER->value => 1,
            MembershipRole::MEMBER->value => 2,
            MembershipRole::MODERATOR->value => 3,
            MembershipRole::ADMINISTRATOR->value => 4,
        ];

        $target_role = $this->target_trooper->membership_role;
        $source_role = $this->source_trooper->membership_role;

        return ($rankings[$source_role->value] ?? -1) > ($rankings[$target_role->value] ?? -1)
            ? $source_role
            : $target_role;
    }

    private function resolveNotificationFrequency(): NotificationFrequency
    {
        $rankings = [
            NotificationFrequency::NEVER->value => 0,
            NotificationFrequency::DAILY->value => 1,
            NotificationFrequency::INSTANT->value => 2,
        ];

        $target_frequency = $this->target_trooper->notification_frequency;
        $source_frequency = $this->source_trooper->notification_frequency;

        return ($rankings[$source_frequency->value] ?? -1) > ($rankings[$target_frequency->value] ?? -1)
            ? $source_frequency
            : $target_frequency;
    }

    /**
     * @return array<mixed>|null
     */
    private function mergeNotificationPreferences(): ?array
    {
        $target_preferences = $this->target_trooper->notification_preferences;
        $source_preferences = $this->source_trooper->notification_preferences;

        if ($target_preferences === null)
        {
            return $source_preferences;
        }

        if ($source_preferences === null)
        {
            return $target_preferences;
        }

        return array_replace_recursive($source_preferences, $target_preferences);
    }

    private function resolveGuardianId(): ?int
    {
        $target_guardian_id = $this->target_trooper->guardian_id;

        if ($target_guardian_id !== null)
        {
            return $target_guardian_id;
        }

        $source_guardian_id = $this->source_trooper->guardian_id;

        if ($source_guardian_id === $this->target_trooper->id)
        {
            return null;
        }

        return $source_guardian_id;
    }

    private function latestDateTime(mixed $target_value, mixed $source_value): mixed
    {
        if ($target_value === null)
        {
            return $source_value;
        }

        if ($source_value === null)
        {
            return $target_value;
        }

        return $source_value->greaterThan($target_value) ? $source_value : $target_value;
    }
}
