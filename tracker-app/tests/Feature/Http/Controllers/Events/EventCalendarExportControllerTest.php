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

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('events.display-ics', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_ics_for_valid_event(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display-ics', $event));

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="event-'.$event->id.'.ics"');

        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('SUMMARY:'.$event->name, $content);
    }

    public function test_invoke_uses_default_end_when_missing(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::EVENT_END => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display-ics', $event));

        // Assert
        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('DTSTART', $content);
        $this->assertStringContainsString('DTEND', $content);
    }

    public function test_invoke_returns_not_found_when_start_missing(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::EVENT_START => null,
            Event::EVENT_END => null,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display-ics', $event));

        // Assert
        $response->assertNotFound();
    }
}
