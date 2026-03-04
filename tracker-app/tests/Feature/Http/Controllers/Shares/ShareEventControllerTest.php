<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Shares;

use App\Models\Event;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the ShareEventController.
 *
 * Validates that the event sharing page displays correctly with
 * and without event upload images.
 */
class ShareEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_share_event_page(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('shares.event', ['event' => $event]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('event_upload', null);
    }

    public function test_invoke_displays_share_event_page_with_event_upload(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $event_upload = EventUpload::factory()->create([
            EventUpload::EVENT_ID => $event->id,
        ]);

        // Act
        $response = $this->get(route('shares.event', [
            'event' => $event,
            'event_upload' => $event_upload->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('event_upload', function ($view_upload) use ($event_upload)
        {
            return $view_upload->id === $event_upload->id;
        });
    }

    public function test_invoke_ignores_event_upload_from_different_event(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $event_upload = EventUpload::factory()->create([
            EventUpload::EVENT_ID => $other_event->id,
        ]);

        // Act
        $response = $this->get(route('shares.event', [
            'event' => $event,
            'event_upload' => $event_upload->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('event_upload', null);
    }

    public function test_invoke_handles_non_existent_event_upload_id(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('shares.event', [
            'event' => $event,
            'event_upload' => 99999,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.event');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('event_upload', null);
    }
}
