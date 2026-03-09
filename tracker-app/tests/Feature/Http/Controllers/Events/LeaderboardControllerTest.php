<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_leaderboard_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('events.leaderboard', ['days' => 30]));

        $response->assertOk();
        $response->assertViewIs('pages.events.leaderboard');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.leaderboard'));

        $response->assertRedirect(route('auth.login'));
    }
}
