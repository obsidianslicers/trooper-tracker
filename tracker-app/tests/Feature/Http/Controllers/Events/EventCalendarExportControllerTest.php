<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCalendarExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_exports_event_as_ics(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('events.display-ics', ['event' => $event->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('events.display-ics', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
