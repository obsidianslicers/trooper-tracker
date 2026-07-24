<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Enums\MembershipStatus;
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
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
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
        MergeTrooperAchievements::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperDonations::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperFriends::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
        MergeTrooperRequests::call(
            target_trooper: $this->target_trooper,
            source_trooper: $this->source_trooper,
        );
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
        $this->source_trooper->membership_status = MembershipStatus::INACTIVE;
        $this->source_trooper->save();
    }
}
