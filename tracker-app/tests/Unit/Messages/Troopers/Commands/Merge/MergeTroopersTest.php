<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Messages\Troopers\Commands\Merge\MergeTrooperCostumes;
use App\Messages\Troopers\Commands\Merge\MergeTrooperFriends;
use App\Messages\Troopers\Commands\Merge\MergeTroopers;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use ReflectionMethod;
use RuntimeException;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class MergeTroopersTest extends TestCase
{
    use FakesMessages;
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
            ->asSystemOwner()
            ->withNotificationFrequency(NotificationFrequency::INSTANT)
            ->create([
                Trooper::PHONE => '404-555-1212',
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

        $this->assertSame('404-555-1212', $target_trooper->{Trooper::PHONE});
        $this->assertSame(MembershipStatus::ACTIVE, $target_trooper->{Trooper::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipRole::SYSTEM_OWNER, $target_trooper->{Trooper::MEMBERSHIP_ROLE});
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

    public function test_resolve_membership_role_ranks_event_coordinator_between_member_and_moderator(): void
    {
        $cases = [
            [MembershipRole::MEMBER, MembershipRole::EVENT_COORDINATOR, MembershipRole::EVENT_COORDINATOR],
            [MembershipRole::EVENT_COORDINATOR, MembershipRole::MEMBER, MembershipRole::EVENT_COORDINATOR],
            [MembershipRole::EVENT_COORDINATOR, MembershipRole::MODERATOR, MembershipRole::MODERATOR],
        ];

        foreach ($cases as [$target_role, $source_role, $expected])
        {
            $target_trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_ROLE => $target_role]);
            $source_trooper = Trooper::factory()->create([Trooper::MEMBERSHIP_ROLE => $source_role]);

            $method = new ReflectionMethod(MergeTroopers::class, 'resolveMembershipRole');
            $method->setAccessible(true);

            $this->assertSame($expected, $method->invoke(new MergeTroopers($target_trooper, $source_trooper)));
        }
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

    public function test_handle_merges_relationships_and_marks_source_merged(): void
    {
        $target_trooper = Trooper::factory()->asPending()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $friend = Trooper::factory()->create();
        TrooperFriend::factory()->forTrooper($source_trooper)->forFriend($friend)->create();

        $subject = new MergeTroopers($target_trooper, $source_trooper);
        $subject->handle();

        $this->assertSame(MembershipStatus::MERGED, $source_trooper->fresh()->membership_status);
        $this->assertSame(MembershipStatus::ACTIVE, $target_trooper->fresh()->membership_status);
        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $target_trooper->id,
            TrooperFriend::FRIEND_ID => $friend->id,
        ]);
    }

    public function test_handle_reports_failure_and_rolls_back(): void
    {
        Exceptions::fake();
        $target_trooper = Trooper::factory()->asPending()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $this->fakeMessages([
            MergeTrooperCostumes::class => fn (array $params): never => throw new RuntimeException('boom'),
        ]);

        $subject = new MergeTroopers($target_trooper, $source_trooper);
        $subject->handle();

        Exceptions::assertReported(RuntimeException::class);
        $this->assertSame(MembershipStatus::ACTIVE, $source_trooper->fresh()->membership_status);
        $this->assertSame(MembershipStatus::PENDING, $target_trooper->fresh()->membership_status);
    }

    public function test_merge_keeps_whichever_notification_preferences_exist(): void
    {
        $target_trooper = Trooper::factory()->create([Trooper::NOTIFICATION_PREFERENCES => null]);
        $source_trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_PREFERENCES => ['events' => ['mail' => false]],
        ]);

        $this->invokeMergeAccounts($target_trooper, $source_trooper);
        $this->assertSame(['events' => ['mail' => false]], $target_trooper->fresh()->notification_preferences);

        $other_source = Trooper::factory()->create([Trooper::NOTIFICATION_PREFERENCES => null]);
        $this->invokeMergeAccounts($target_trooper->fresh(), $other_source);
        $this->assertSame(['events' => ['mail' => false]], $target_trooper->fresh()->notification_preferences);
    }

    public function test_merge_keeps_target_guardian_and_drops_source_guardian_pointing_at_target(): void
    {
        $guardian = Trooper::factory()->create();
        $target_trooper = Trooper::factory()->create([Trooper::GUARDIAN_ID => $guardian->id]);
        $source_trooper = Trooper::factory()->create([Trooper::GUARDIAN_ID => Trooper::factory()->create()->id]);

        $this->invokeMergeAccounts($target_trooper, $source_trooper);
        $this->assertSame($guardian->id, $target_trooper->fresh()->guardian_id);

        $minor_target = Trooper::factory()->create([Trooper::GUARDIAN_ID => null]);
        $minor_source = Trooper::factory()->create([Trooper::GUARDIAN_ID => $minor_target->id]);

        $this->invokeMergeAccounts($minor_target, $minor_source);
        $this->assertNull($minor_target->fresh()->guardian_id);
    }

    public function test_merge_keeps_latest_visitor_dates_when_one_side_is_null(): void
    {
        $expires = Carbon::parse('2026-09-01 00:00:00');
        $target_trooper = Trooper::factory()->create([Trooper::VISITOR_EXPIRES_AT => $expires]);
        $source_trooper = Trooper::factory()->create([Trooper::VISITOR_EXPIRES_AT => null]);

        $this->invokeMergeAccounts($target_trooper, $source_trooper);

        $this->assertSame(
            '2026-09-01 00:00:00',
            $target_trooper->fresh()->visitor_expires_at?->format('Y-m-d H:i:s')
        );
    }

    private function invokeMergeAccounts(Trooper $target_trooper, Trooper $source_trooper): void
    {
        $subject = new MergeTroopers($target_trooper, $source_trooper);
        $method = new ReflectionMethod($subject, 'mergeTrooperAccounts');
        $method->invoke($subject);
    }
}
