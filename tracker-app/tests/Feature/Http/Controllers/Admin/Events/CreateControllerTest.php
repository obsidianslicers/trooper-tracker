<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Events CreateController.
 *
 * Verifies:
 * - Administrators can view the event creation form
 * - Moderators can view the event creation form
 * - New event is initialized with default values
 * - Organization can be assigned via query parameter
 * - Organization hierarchy is passed to view
 * - Available organizations are passed to view
 * - Correct view is rendered
 * - Authentication is required
 * - Authorization is enforced
 * - Old form input is preserved on validation errors
 */
class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.events.create'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_create_form(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.create');
    }

    public function test_invoke_moderator_can_view_create_form(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.create');
    }

    public function test_invoke_regular_trooper_cannot_view_create_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_initializes_new_event_with_regular_type(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertViewHas('event', function ($event)
        {
            return $event->type === EventType::REGULAR;
        });
    }

    public function test_invoke_initializes_new_event_with_draft_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertViewHas('event', function ($event)
        {
            return $event->status === EventStatus::DRAFT;
        });
    }

    public function test_invoke_passes_organization_hierarchy_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Organization::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertViewHas('organization_hierarchy');
        $hierarchy = $response->viewData('organization_hierarchy');
        $this->assertNotEmpty($hierarchy);
    }

    public function test_invoke_passes_organizations_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Organization::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertViewHas('organizations');
        $organizations = $response->viewData('organizations');
        $this->assertGreaterThanOrEqual(5, $organizations->count());
    }

    public function test_invoke_assigns_organization_from_query_parameter(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.events.create', ['organization_id' => $organization->id]));

        // Assert
        $response->assertViewHas('event', function ($event) use ($organization)
        {
            return $event->organization_id == $organization->id
                && $event->organization->id == $organization->id;
        });
    }

    public function test_invoke_moderator_cannot_assign_non_moderated_organization(): void
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

        // Act & Assert
        $this->actingAs($moderator)
            ->get(route('admin.events.create', ['organization_id' => $other_org->id]))
            ->assertNotFound();
    }

    public function test_invoke_preserves_old_input_on_validation_error(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Simulate old input
        session()->put('_old_input', [
            Event::NAME => 'Previously Entered Name',
            Event::VENUE => 'Previously Entered Venue',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        dd($response);

        // Assert
        $response->assertViewHas('event', function ($event)
        {
            return $event->name === 'Previously Entered Name'
                && $event->venue === 'Previously Entered Venue';
        });
    }

    public function test_invoke_passes_event_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.events.create'));

        // Assert
        $response->assertViewHas('event');
        $event = $response->viewData('event');
        $this->assertInstanceOf(Event::class, $event);
        $this->assertNull($event->id); // New event, not saved
    }
}
