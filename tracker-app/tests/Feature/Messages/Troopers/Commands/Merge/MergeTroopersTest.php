<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands\Merge;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Messages\Troopers\Commands\Merge\MergeTrooperFriends;
use App\Messages\Troopers\Commands\Merge\MergeTroopers;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class MergeTroopersTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_trooper_accounts_promotes_target_and_copies_missing_trooper_fields(): void
    {
        $guardian = Trooper::factory()->asActive()->create();

        $target_trooper = Trooper::factory()
            ->asPending()
            ->withNotificationFrequency(NotificationFrequency::NEVER)
            ->create([
                Trooper::PHONE => null,
                Trooper::PUSH_NOTIFICATIONS_ENABLED => false,
                Trooper::NOTIFICATION_PREFERENCES => [
                    'events' => ['instant' => false],
                ],
                Trooper::SETUP_COMPLETED_AT => null,
                Trooper::LAST_ACTIVE_AT => Carbon::parse('2026-07-20 10:00:00'),
                Trooper::ACHIEVEMENTS_UPDATED_AT => Carbon::parse('2026-07-01 10:00:00'),
                Trooper::GUARDIAN_ID => null,
                Trooper::DATE_OF_BIRTH => null,
            ]);

        $source_trooper = Trooper::factory()
            ->asAdministrator()
            ->withNotificationFrequency(NotificationFrequency::INSTANT)
            ->create([
                Trooper::PHONE => '555-1212',
                Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
                Trooper::NOTIFICATION_PREFERENCES => [
                    'events' => ['daily' => true],
                    'notices' => ['email' => true],
                ],
                Trooper::SETUP_COMPLETED_AT => Carbon::parse('2026-07-10 10:00:00'),
                Trooper::LAST_ACTIVE_AT => Carbon::parse('2026-07-22 10:00:00'),
                Trooper::ACHIEVEMENTS_UPDATED_AT => Carbon::parse('2026-07-21 10:00:00'),
                Trooper::GUARDIAN_ID => $guardian->id,
                Trooper::DATE_OF_BIRTH => Carbon::parse('2000-01-01'),
                Trooper::VISITOR_EXPIRES_AT => Carbon::parse('2026-08-01 00:00:00'),
                Trooper::VISITOR_NOTIFIED_AT => Carbon::parse('2026-07-22 09:00:00'),
                Trooper::DELETION_REQUESTED_AT => Carbon::parse('2026-07-15 00:00:00'),
            ]);

        $subject = new MergeTroopers($target_trooper, $source_trooper);

        $method = new ReflectionMethod($subject, 'mergeTrooperAccounts');
        $method->setAccessible(true);
        $method->invoke($subject);

        $target_trooper->refresh();
        $source_trooper->refresh();

        $this->assertSame('555-1212', $target_trooper->{Trooper::PHONE});
        $this->assertSame(MembershipStatus::ACTIVE, $target_trooper->{Trooper::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipRole::ADMINISTRATOR, $target_trooper->{Trooper::MEMBERSHIP_ROLE});
        $this->assertSame(
            NotificationFrequency::INSTANT,
            $target_trooper->{Trooper::NOTIFICATION_FREQUENCY},
        );
        $this->assertTrue($target_trooper->{Trooper::PUSH_NOTIFICATIONS_ENABLED});
        $this->assertSame([
            'events' => ['daily' => true, 'instant' => false],
            'notices' => ['email' => true],
        ], $target_trooper->{Trooper::NOTIFICATION_PREFERENCES});
        $this->assertSame('2026-07-10 10:00:00', $target_trooper->{Trooper::SETUP_COMPLETED_AT}?->format('Y-m-d H:i:s'));
        $this->assertSame($guardian->id, $target_trooper->{Trooper::GUARDIAN_ID});
        $this->assertSame('2000-01-01 00:00:00', $target_trooper->{Trooper::DATE_OF_BIRTH}?->format('Y-m-d H:i:s'));
        $this->assertSame(
            '2026-08-01 00:00:00',
            $target_trooper->{Trooper::VISITOR_EXPIRES_AT}?->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            '2026-07-22 09:00:00',
            $target_trooper->{Trooper::VISITOR_NOTIFIED_AT}?->format('Y-m-d H:i:s'),
        );

        $this->assertSame(MembershipStatus::MERGED, $source_trooper->{Trooper::MEMBERSHIP_STATUS});
        $this->assertNull($source_trooper->{Trooper::VISITOR_EXPIRES_AT});
        $this->assertNull($source_trooper->{Trooper::VISITOR_NOTIFIED_AT});
        $this->assertNull($source_trooper->{Trooper::DELETION_REQUESTED_AT});
    }

    public function test_merge_trooper_friends_moves_source_friendships_to_target_and_updates_inverse_rows(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $shared_friend = Trooper::factory()->asActive()->create();
        $moved_friend = Trooper::factory()->asActive()->create();

        TrooperFriend::factory()
            ->forTrooper($target_trooper)
            ->forFriend($shared_friend)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($shared_friend)
            ->forFriend($target_trooper)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($source_trooper)
            ->forFriend($shared_friend)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($shared_friend)
            ->forFriend($source_trooper)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($source_trooper)
            ->forFriend($moved_friend)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($moved_friend)
            ->forFriend($source_trooper)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($source_trooper)
            ->forFriend($target_trooper)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($target_trooper)
            ->forFriend($source_trooper)
            ->create();

        $subject = new MergeTrooperFriends($target_trooper, $source_trooper);
        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $target_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $shared_friend->{Trooper::ID},
        ]);
        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $shared_friend->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $target_trooper->{Trooper::ID},
        ]);
        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $target_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $moved_friend->{Trooper::ID},
        ]);
        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $moved_friend->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $target_trooper->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $source_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $shared_friend->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $shared_friend->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $source_trooper->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $source_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $moved_friend->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $moved_friend->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $source_trooper->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $target_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $source_trooper->{Trooper::ID},
        ]);
        $this->assertDatabaseMissing('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $source_trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $target_trooper->{Trooper::ID},
        ]);
        $this->assertSame(4, TrooperFriend::query()->count());
    }
}