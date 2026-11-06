<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Events\EventWatchChanging;
use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
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

    public function test_invoke_leaves_watch_unchanged_when_a_listener_refuses(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        EventFacade::listen(EventWatchChanging::class, fn (EventWatchChanging $changing): bool => false);

        $this->actingAs($trooper)->post(route('events.toggle-watch-htmx', $event))->assertOk();

        $this->assertDatabaseMissing('tt_event_watches', ['event_id' => $event->id, 'trooper_id' => $trooper->id]);
    }
}
