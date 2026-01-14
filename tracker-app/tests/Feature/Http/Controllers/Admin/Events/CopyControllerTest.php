<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Events CopyController.
 *
 * Verifies:
 * - Administrators can view the event copy form
 * - Moderators can view the copy form for events they moderate
 * - Event name is prepended with "COPY OF"
 * - Source event data is passed to the view
 * - Correct view is rendered
 * - Authentication is required
 * - Authorization is enforced
 */
class CopyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_copy_form(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::NAME => 'Original Event Name',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.copy');
    }

    public function test_invoke_moderator_can_view_copy_form_for_moderated_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Moderated Event',
        ]);

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.copy');
    }

    public function test_invoke_moderator_cannot_view_copy_form_for_non_moderated_events(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $other_org->id,
        ]);

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_prepends_copy_of_to_event_name(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::NAME => 'Original Event',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertViewHas('event', function ($view_event)
        {
            return $view_event->name === 'COPY OF Original Event';
        });
    }

    public function test_invoke_passes_event_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::NAME => 'Test Event',
            Event::VENUE => 'Test Venue',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertViewHas('event');
        $view_event = $response->viewData('event');
        $this->assertEquals($event->id, $view_event->id);
        $this->assertEquals('Test Venue', $view_event->venue);
    }

    public function test_invoke_preserves_source_event_data(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::NAME => 'Convention Appearance',
            Event::VENUE => 'Convention Center',
            Event::VENUE_CITY => 'Springfield',
            Event::CONTACT_NAME => 'John Doe',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $view_event = $response->viewData('event');
        $this->assertEquals('Convention Center', $view_event->venue);
        $this->assertEquals('Springfield', $view_event->venue_city);
        $this->assertEquals('John Doe', $view_event->contact_name);
    }

    public function test_invoke_requires_trooper_with_update_permission(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.events.copy', compact('event')));

        // Assert
        $response->assertForbidden();
    }
}
