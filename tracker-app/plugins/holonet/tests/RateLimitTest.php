<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Tests;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Holonet\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;
use TroopTracker\Plugins\Holonet\Support\Pkce;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;
    use UsesHolonetPlugin;

    private const HISTORY = self::GEORGIA.'/webhooks/holonet/history';

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrackers();
        $this->enableHolonet();
        $this->trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
    }

    public function test_history_requests_are_limited_per_key(): void
    {
        config(['plugins.holonet.limits.history_per_key' => 2]);

        $this->withToken('key-1')->postJson('/webhooks/holonet/history')->assertStatus(401);
        $this->withToken('key-1')->postJson('/webhooks/holonet/history')->assertStatus(401);
        $this->withToken('key-1')->postJson('/webhooks/holonet/history')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
        $this->withToken('key-2')->postJson('/webhooks/holonet/history')->assertStatus(401);
    }

    public function test_history_requests_are_limited_per_address(): void
    {
        config(['plugins.holonet.limits.history_per_ip' => 2]);

        $this->withToken('key-1')->postJson('/webhooks/holonet/history')->assertStatus(401);
        $this->withToken('key-2')->postJson('/webhooks/holonet/history')->assertStatus(401);
        $this->withToken('key-3')->postJson('/webhooks/holonet/history')->assertStatus(429);
    }

    public function test_code_exchanges_and_revokes_are_limited_per_address(): void
    {
        config([
            'plugins.holonet.limits.token_per_ip' => 1,
            'plugins.holonet.limits.revoke_per_ip' => 1,
        ]);

        $this->postJson('/webhooks/holonet/token', ['code' => 'x'])->assertStatus(400);
        $this->postJson('/webhooks/holonet/token', ['code' => 'x'])->assertStatus(429);
        $this->postJson('/webhooks/holonet/revoke')->assertNoContent();
        $this->postJson('/webhooks/holonet/revoke')->assertStatus(429);
    }

    public function test_a_trooper_cannot_refresh_a_link_again_right_away(): void
    {
        $link = $this->link();
        $link->update([HolonetLink::REFRESHED_AT => now()->subMinutes(2)]);
        $this->fakeTrackers([self::HISTORY => Http::response(['troops' => []])]);

        $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.links.refresh', ['link' => $link]))
            ->assertRedirect(route('plugins.holonet.index'));

        Http::assertNotSent(fn (Request $request): bool => $request->url() === self::HISTORY);

        $this->travel(4)->minutes();
        $this->actingAs($this->trooper)->post(route('plugins.holonet.links.refresh', ['link' => $link]));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::HISTORY);
    }

    public function test_starting_links_is_limited_per_hour(): void
    {
        config(['plugins.holonet.limits.links_per_hour' => 1]);

        $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.links.start'), ['tracker_url' => self::GEORGIA])
            ->assertRedirectContains(self::GEORGIA);
        $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.links.start'), ['tracker_url' => self::GEORGIA])
            ->assertRedirect(route('plugins.holonet.index'));
    }

    public function test_allowing_links_is_limited_per_hour(): void
    {
        config(['plugins.holonet.limits.links_per_hour' => 1]);
        $query = [
            'client' => self::GEORGIA,
            'redirect_uri' => self::GEORGIA.'/plugins/holonet/callback',
            'state' => 'state-1',
            'code_challenge' => Pkce::challenge(str_repeat('v', 64)),
            'allow' => '1',
        ];

        $this->actingAs($this->trooper)->post(route('plugins.holonet.authorize.submit'), $query)
            ->assertRedirectContains('code=');
        $this->actingAs($this->trooper)->post(route('plugins.holonet.authorize.submit'), $query)
            ->assertStatus(429);
    }

    public function test_a_tracker_that_asks_to_slow_down_is_left_alone_until_the_wait_is_over(): void
    {
        $link = $this->link();
        $this->fakeTrackers([self::HISTORY => Http::response(['error' => 'too_many_requests'], 429, [
            'Retry-After' => '120',
        ])]);

        $this->assertFalse(RefreshTrackerLink::call(link: $link));
        $this->assertFalse(RefreshTrackerLink::call(link: $link));

        $this->assertSame(1, $this->historyRequests());
        $this->assertStringContainsString('slow down', (string) $link->fresh()->last_error);
        $this->assertSame(HolonetLink::STATUS_ACTIVE, $link->fresh()->status);

        $this->travel(3)->minutes();
        RefreshTrackerLink::call(link: $link);
        $this->assertSame(2, $this->historyRequests());
    }

    private function historyRequests(): int
    {
        return Http::recorded(fn (Request $request): bool => $request->url() === self::HISTORY)->count();
    }

    private function link(): HolonetLink
    {
        return HolonetLink::query()->create([
            HolonetLink::TROOPER_ID => $this->trooper->id,
            HolonetLink::TRACKER_URL => self::GEORGIA,
            HolonetLink::TRACKER_NAME => '501st Georgia Garrison',
            HolonetLink::TOKEN => 'access-key',
        ]);
    }
}
