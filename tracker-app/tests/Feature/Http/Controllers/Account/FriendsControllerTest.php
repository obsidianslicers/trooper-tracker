<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_friends_page_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $friend = Trooper::factory()->asActive()->create();

        TrooperFriend::factory()
            ->forTrooper($trooper)
            ->forFriend($friend)
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.friends'));

        $response->assertOk();
        $response->assertViewIs('pages.account.friends');
        $response->assertViewHas('friends', function ($friends) use ($friend) {
            return $friends->count() === 1
                && $friends->first()->friend->id === $friend->id;
        });
    }

    public function test_invoke_excludes_friend_links_pointing_to_a_deleted_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $deleted_friend = Trooper::factory()->asActive()->create();

        TrooperFriend::factory()
            ->forTrooper($trooper)
            ->forFriend($deleted_friend)
            ->create();

        $deleted_friend->delete();

        $response = $this->actingAs($trooper)->get(route('account.friends'));

        $response->assertOk();
        $response->assertViewHas('friends', fn ($friends) => $friends->isEmpty());
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.friends'));

        $response->assertRedirect(route('auth.login'));
    }
}
