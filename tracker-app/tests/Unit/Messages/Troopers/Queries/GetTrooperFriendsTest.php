<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetTrooperFriends;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperFriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_direct_friends_sorted_by_display_name(): void
    {
        $trooper = Trooper::factory()->create();
        $zulu_friend = Trooper::factory()->withDisplayName('Zulu Friend')->create();
        $alpha_friend = Trooper::factory()->withDisplayName('Alpha Friend')->create();
        $non_friend = Trooper::factory()->withDisplayName('Not A Friend')->create();

        TrooperFriend::factory()->forTrooper($trooper)->forFriend($zulu_friend)->create();
        TrooperFriend::factory()->forTrooper($trooper)->forFriend($alpha_friend)->create();
        TrooperFriend::factory()->forTrooper($non_friend)->forFriend($trooper)->create();

        $subject = new GetTrooperFriends($trooper);

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame(
            ['Alpha Friend', 'Zulu Friend'],
            $result->pluck(Trooper::DISPLAY_NAME)->all(),
        );
        $this->assertFalse($result->contains(fn(Trooper $candidate): bool => $candidate->is($non_friend)));
    }
}
