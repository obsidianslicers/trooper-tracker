<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * 
 * 
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 * 
 */
final class MergeTroopers extends Message
{
    public function __construct(
        public readonly Trooper $target_trooper,
        public readonly Trooper $source_trooper,
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
        });
    }
}