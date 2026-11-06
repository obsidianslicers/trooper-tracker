<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Tests;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Holonet\Http\Controllers\StartTrackerLinkController;
use TroopTracker\Plugins\Holonet\Models\HolonetCode;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;
use TroopTracker\Plugins\Holonet\Support\Pkce;

class LinkFlowTest extends TestCase
{
    use RefreshDatabase;
    use UsesHolonetPlugin;

    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeTrackers();
        $this->enableHolonet();
        $this->trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
    }

    public function test_page_offers_listed_trackers_but_not_this_one(): void
    {
        $this->actingAs($this->trooper)->get(route('plugins.holonet.index'))
            ->assertOk()
            ->assertSee('501st Georgia Garrison')
            ->assertDontSee('<option value="https://florida.test">', false)
            ->assertDontSee('Broken');
    }

    public function test_start_sends_the_trooper_to_the_other_tracker(): void
    {
        $response = $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.links.start'), ['tracker_url' => self::GEORGIA]);

        $pending = session(StartTrackerLinkController::SESSION_KEY);
        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith(self::GEORGIA.'/plugins/holonet/authorize?', $location);
        $this->assertSame('https://florida.test', $query['client']);
        $this->assertSame('https://florida.test/plugins/holonet/callback', $query['redirect_uri']);
        $this->assertSame($pending['state'], $query['state']);
        $this->assertSame(Pkce::challenge($pending['verifier']), $query['code_challenge']);
    }

    public function test_start_refuses_an_unlisted_tracker(): void
    {
        $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.links.start'), ['tracker_url' => 'https://evil.test'])
            ->assertRedirect(route('plugins.holonet.index'));

        $this->assertNull(session(StartTrackerLinkController::SESSION_KEY));
    }

    public function test_minors_cannot_start_a_link(): void
    {
        $minor = Trooper::factory()->asMember()->withVerifiedEmail()
            ->withGuardian(Trooper::factory()->create())->create();

        $this->actingAs($minor)
            ->post(route('plugins.holonet.links.start'), ['tracker_url' => self::GEORGIA])
            ->assertRedirect(route('plugins.holonet.index'));

        $this->assertNull(session(StartTrackerLinkController::SESSION_KEY));
    }

    public function test_callback_swaps_the_code_saves_the_link_and_copies_troops(): void
    {
        $this->fakeTrackers([
            self::GEORGIA.'/webhooks/holonet/token' => Http::response([
                'token' => 'access-key',
                'tracker' => ['name' => 'Georgia', 'url' => self::GEORGIA],
                'trooper' => ['display_name' => 'TK-1 Georgia'],
            ]),
            self::GEORGIA.'/webhooks/holonet/history' => Http::response([
                'version' => 1,
                'trooper' => ['display_name' => 'TK-1 Georgia'],
                'troops' => [[
                    'id' => '7',
                    'event_name' => 'Hospital visit',
                    'shift_starts_at' => '2026-01-01T10:00:00+00:00',
                    'shift_ends_at' => '2026-01-01T12:00:00+00:00',
                ]],
            ]),
        ]);

        $this->withSession([StartTrackerLinkController::SESSION_KEY => [
            'state' => 'state-1',
            'verifier' => 'verifier-1',
            'tracker_url' => self::GEORGIA,
        ]]);

        $this->actingAs($this->trooper)
            ->get(route('plugins.holonet.callback', ['code' => 'code-1', 'state' => 'state-1']))
            ->assertRedirect(route('plugins.holonet.index'));

        Http::assertSent(fn (Request $request): bool => $request->url() === self::GEORGIA.'/webhooks/holonet/token'
            && $request['code'] === 'code-1'
            && $request['code_verifier'] === 'verifier-1'
            && $request['client'] === 'https://florida.test');

        $link = HolonetLink::sole();
        $this->assertSame($this->trooper->id, $link->trooper_id);
        $this->assertSame('access-key', $link->token);
        $this->assertSame('501st Georgia Garrison', $link->tracker_name);
        $this->assertSame('TK-1 Georgia', $link->remote_display_name);
        $this->assertSame(['Hospital visit'], $link->troops->pluck('event_name')->all());
    }

    public function test_callback_with_the_wrong_state_links_nothing(): void
    {
        $this->withSession([StartTrackerLinkController::SESSION_KEY => [
            'state' => 'state-1',
            'verifier' => 'verifier-1',
            'tracker_url' => self::GEORGIA,
        ]]);

        $this->actingAs($this->trooper)
            ->get(route('plugins.holonet.callback', ['code' => 'code-1', 'state' => 'other']))
            ->assertRedirect(route('plugins.holonet.index'));

        $this->assertSame(0, HolonetLink::count());
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'token'));
    }

    public function test_authorize_page_asks_the_trooper_for_a_listed_tracker(): void
    {
        $this->actingAs($this->trooper)
            ->get(route('plugins.holonet.authorize', $this->authorizeQuery()))
            ->assertOk()
            ->assertSee('Let 501st Georgia Garrison see your troop history?')
            ->assertSee('Allow');
    }

    public function test_authorize_page_refuses_a_redirect_to_another_site(): void
    {
        $this->actingAs($this->trooper)
            ->get(route('plugins.holonet.authorize', $this->authorizeQuery([
                'redirect_uri' => 'https://evil.test/plugins/holonet/callback',
            ])))
            ->assertOk()
            ->assertSee('isn&#039;t valid', false)
            ->assertDontSee('Allow');
    }

    public function test_allowing_sends_a_one_time_code_back(): void
    {
        $response = $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.authorize.submit'), $this->authorizeQuery() + ['allow' => '1']);

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith(self::GEORGIA.'/plugins/holonet/callback?', $location);
        $this->assertSame('state-1', $query['state']);
        $code = HolonetCode::sole();
        $this->assertSame(hash('sha256', $query['code']), $code->code_hash);
        $this->assertSame(self::GEORGIA, $code->tracker_url);
        $this->assertSame($this->trooper->id, $code->trooper_id);
    }

    public function test_cancelling_sends_no_code(): void
    {
        $response = $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.authorize.submit'), $this->authorizeQuery() + ['allow' => '0']);

        $this->assertStringContainsString('error=access_denied', (string) $response->headers->get('Location'));
        $this->assertSame(0, HolonetCode::count());
    }

    public function test_no_code_is_given_when_sharing_is_off(): void
    {
        $this->app->make(\App\Plugins\PluginManager::class)
            ->updateSettings('holonet', ['share_history' => false]);

        $response = $this->actingAs($this->trooper)
            ->post(route('plugins.holonet.authorize.submit'), $this->authorizeQuery() + ['allow' => '1']);

        $this->assertStringContainsString('error=access_denied', (string) $response->headers->get('Location'));
        $this->assertSame(0, HolonetCode::count());
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function authorizeQuery(array $overrides = []): array
    {
        return $overrides + [
            'client' => self::GEORGIA,
            'redirect_uri' => self::GEORGIA.'/plugins/holonet/callback',
            'state' => 'state-1',
            'code_challenge' => Pkce::challenge(str_repeat('v', 64)),
        ];
    }
}
