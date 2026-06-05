<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\OauthProvider;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ToggleEventWatchHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_watch_for_unauthenticated_trooper(): void
    {
        $response = $this->post(route('events.toggle-watch-htmx', Event::factory()->create()));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_watch_when_not_watching(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event));

        $response->assertOk();
        $this->assertDatabaseHas('tt_event_watches', [
            'event_id' => $event->id,
            'trooper_id' => $trooper->id,
        ]);
    }

    public function test_invoke_removes_watch_when_already_watching(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        $response = $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event));

        $response->assertOk();
        $this->assertDatabaseMissing('tt_event_watches', [
            'event_id' => $event->id,
            'trooper_id' => $trooper->id,
        ]);
    }

    public function test_invoke_skips_xenforo_when_integration_not_configured(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => '']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 99]);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_skips_xenforo_when_event_has_no_thread_id(): void
    {
        config(['services.xenforo.base_url' => 'https://xf.test', 'services.xenforo.api_key' => 'key']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => null]);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_skips_xenforo_when_trooper_has_no_linked_account(): void
    {
        config(['services.xenforo.base_url' => 'https://xf.test', 'services.xenforo.api_key' => 'key']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 55]);

        Http::fake();

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_calls_xenforo_watch_when_creating_watch(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 42]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
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
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 42]);

        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
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
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 42]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['error' => 'Thread not found.'], 500)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        $this->assertDatabaseMissing('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }

    public function test_invoke_aborts_watch_removal_when_xenforo_returns_error(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create(['thread_id' => 42]);

        EventWatch::create(['event_id' => $event->id, 'trooper_id' => $trooper->id]);

        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '777',
        ]);

        Http::fake(['https://xf.test/api/trooper-api/watch-thread' => Http::response(['error' => 'You must be logged in.'], 422)]);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        $this->assertDatabaseHas('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }
}
