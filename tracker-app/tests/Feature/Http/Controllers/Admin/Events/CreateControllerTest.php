<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_creation_form_for_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.create');
        $response->assertViewHas('event');
    }

    public function test_invoke_displays_event_creation_form_for_moderator(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.create');
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_unauthenticated_user(): void
    {
        // Act
        $response = $this->get(route('admin.events.create'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_copies_event_data_when_copy_event_parameter_provided(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $existing_event = Event::factory()->create([
            'name' => 'Original Event',
            'venue' => 'Original Venue',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.create', [
            'copy_id' => $existing_event->id,
        ]));

        // Assert - Should create a new event and redirect to its update page
        $new_event = Event::where('name', 'Copy of Original Event')->first();
        $this->assertNotNull($new_event);
        $this->assertEquals('Original Venue', $new_event->venue);
        $response->assertRedirect(route('admin.events.update', ['event' => $new_event->id]));
    }

    public function test_invoke_handles_invalid_copy_event_parameter_gracefully(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.create', [
            'copy_id' => 99999,
        ]));

        // Assert
        $response->assertNotFound();
    }
}
