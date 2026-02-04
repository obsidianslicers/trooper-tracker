<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TaggedUploadsHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can view tagged uploads HTMX partial
 * - Uploads where the trooper is tagged are passed to the view
 */
class TaggedUploadsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_htmx_partial(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.tagged-uploads-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.tagged-uploads');
    }

    public function test_invoke_passes_uploads_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();
        $upload->troopers()->attach($trooper->id);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('dashboard.tagged-uploads-htmx'));

        // Assert
        $response->assertViewHas('uploads');
        $uploads = $response->viewData('uploads');
        $this->assertCount(1, $uploads);
    }

    public function test_invoke_shows_uploads_for_specified_trooper(): void
    {
        // Arrange
        $auth_trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();
        $upload->troopers()->attach($other_trooper->id);

        // Act
        $response = $this->actingAs($auth_trooper)
            ->get(route('dashboard.tagged-uploads-htmx', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertViewHas('uploads');
        $uploads = $response->viewData('uploads');
        $this->assertCount(1, $uploads);
    }
}
