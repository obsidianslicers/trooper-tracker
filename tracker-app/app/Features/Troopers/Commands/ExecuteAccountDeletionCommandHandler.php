<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\AwardTrooper;
use App\Models\EventMissionAck;
use App\Models\EventNotification;
use App\Models\EventShare;
use App\Models\EventWatch;
use App\Models\MobileDevice;
use App\Models\NoticeTrooper;
use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use App\Models\TrooperFriend;
use App\Models\TrooperNotification;
use App\Models\TrooperOrganization;
use App\Services\StarWarsNameGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @implements CommandHandlerInterface<ExecuteAccountDeletionCommand>
 */
readonly class ExecuteAccountDeletionCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  ExecuteAccountDeletionCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;
        $id = $trooper->id;

        // Hard-delete auth credentials and device tokens
        OauthLogin::withTrashed()->where(OauthLogin::TROOPER_ID, $id)->forceDelete();
        MobileDevice::where(MobileDevice::TROOPER_ID, $id)->delete();
        TrooperNotification::where('notifiable_type', Trooper::class)
            ->where('notifiable_id', $id)
            ->delete();

        // Hard-delete password reset tokens and sessions
        DB::table('tt_password_reset_tokens')->where('email', $trooper->email)->delete();
        DB::table('tt_sessions')->where('user_id', $id)->delete();

        // Soft-delete operational records
        TrooperCostume::where(TrooperCostume::TROOPER_ID, $id)->delete();
        TrooperFriend::where(TrooperFriend::TROOPER_ID, $id)
            ->orWhere(TrooperFriend::FRIEND_ID, $id)
            ->delete();
        TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $id)->delete();
        TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $id)->delete();
        TrooperDonation::where(TrooperDonation::TROOPER_ID, $id)->delete();
        AwardTrooper::where(AwardTrooper::TROOPER_ID, $id)->delete();
        NoticeTrooper::where(NoticeTrooper::TROOPER_ID, $id)->delete();
        EventNotification::where(EventNotification::TROOPER_ID, $id)->delete();
        EventMissionAck::where(EventMissionAck::TROOPER_ID, $id)->delete();
        EventShare::where(EventShare::TROOPER_ID, $id)->delete();
        EventWatch::where(EventWatch::TROOPER_ID, $id)->delete();

        // Anonymize PII in-place so historical FK references remain valid.
        // Non-nullable columns receive placeholder values; nullable columns are cleared.
        $trooper->forceFill([
            'display_name' => StarWarsNameGeneratorService::generate() . ' ✧',
            'legal_name' => 'Deleted Member',
            'email' => "deleted+{$id}@deleted.invalid",
            'password' => Hash::make(Str::random(64)),
            'phone' => null,
            'date_of_birth' => null,
            'remember_token' => null,
            'display_costume_id' => null,
            'deletion_requested_at' => null,
        ])->save();

        $trooper->delete();

        return null;
    }
}
