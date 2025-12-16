<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_update_form_for_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.update', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.update');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('organizations');
    }

    public function test_invoke_displays_event_update_form_for_moderator_with_permission(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        $event = Event::factory()->withOrganization($organization)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.update', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.update');
    }

    public function test_invoke_denies_access_to_moderator_without_permission(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization_1, member: true)
            ->create();

        $event = Event::factory()->withOrganization($organization_2)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.update', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.update', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_displays_draft_warning_for_draft_events(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create(['status' => EventStatus::DRAFT->value]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.update', $event));

        // Assert
        $response->assertOk();
        $response->assertSee('draft', false);
    }

    public function test_invoke_loads_all_organizations_for_selection(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        Organization::factory()->count(2)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.update', $event));

        // Assert
        $response->assertOk();
        $organizations = $response->viewData('organizations');
        $this->assertCount(3, $organizations);
    }
}
