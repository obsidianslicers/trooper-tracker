<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\CreateTrooperFriendshipJob;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTrooperFriendshipJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_serialization_preserves_trooper_and_friend_ids(): void
    {
        $subject = new CreateTrooperFriendshipJob(10, 25);

        $restored = unserialize(serialize($subject));

        $this->assertSame(10, $restored->trooper_id);
        $this->assertSame(25, $restored->friend_id);
    }

    public function test_handle_creates_bidirectional_friendship_records(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $friend = Trooper::factory()->asMember()->create();

        $subject = new CreateTrooperFriendshipJob(
            $trooper->{Trooper::ID},
            $friend->{Trooper::ID}
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $friend->{Trooper::ID},
        ]);
        $this->assertDatabaseHas('tt_trooper_friends', [
            TrooperFriend::TROOPER_ID => $friend->{Trooper::ID},
            TrooperFriend::FRIEND_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function test_handle_does_not_create_duplicate_bidirectional_friendships(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $friend = Trooper::factory()->asMember()->create();

        TrooperFriend::factory()
            ->forTrooper($trooper)
            ->forFriend($friend)
            ->create();

        TrooperFriend::factory()
            ->forTrooper($friend)
            ->forFriend($trooper)
            ->create();

        $subject = new CreateTrooperFriendshipJob(
            $trooper->{Trooper::ID},
            $friend->{Trooper::ID}
        );

        $subject->handle();

        $this->assertSame(2, TrooperFriend::query()->count());
    }
}
