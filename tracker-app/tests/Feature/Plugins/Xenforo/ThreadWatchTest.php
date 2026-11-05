<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\Event;
use App\Models\EventWatch;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;

class ThreadWatchTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_invoke_skips_xenforo_when_integration_not_configured(): void
    {
        config(['plugins.xenforo.base_url' => '', 'plugins.xenforo.api_key' => '']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 99);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_skips_xenforo_when_event_has_no_thread_id(): void
    {
        config(['plugins.xenforo.base_url' => 'https://xf.test', 'plugins.xenforo.api_key' => 'key']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), null);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_skips_xenforo_when_trooper_has_no_linked_account(): void
    {
        config(['plugins.xenforo.base_url' => 'https://xf.test', 'plugins.xenforo.api_key' => 'key']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 55);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_calls_xenforo_watch_when_creating_watch(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 42);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['success' => true, 'watching' => true, 'email_subscribe' => true], 200)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/trooper-api/watch-thread'
                && $request->method() === 'POST'
                && $request->header('XF-Api-User')[0] === '777'
                && ($request['thread_id'] ?? null) == 42
                && ($request['email_subscribe'] ?? null) == true;
        });

        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_calls_xenforo_unwatch_when_removing_watch(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 42);

        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['success' => true, 'watching' => true, 'email_subscribe' => true], 200)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertSent(function ($request)
        {
            return $request->url() === 'https://xf.test/api/trooper-api/watch-thread'
                && $request->method() === 'DELETE'
                && ($request['thread_id'] ?? null) == 42;
        });

        $this->assertDatabaseMissing('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_aborts_watch_creation_when_xenforo_returns_error(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 42);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['error' => 'Thread not found.'], 500)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        $this->assertDatabaseMissing('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_aborts_watch_removal_when_xenforo_returns_error(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 42);

        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['error' => 'You must be logged in.'], 422)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }
}
