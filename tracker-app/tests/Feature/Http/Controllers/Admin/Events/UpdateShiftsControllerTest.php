<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateShiftsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_shifts_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.shifts');
        $response->assertViewHas('event', $event);
    }

    public function test_invoke_passes_shifts_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->create(['event_id' => $event->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $response->assertViewHas('shifts');
    }

    public function test_invoke_administrator_can_manage_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_can_manage_moderated_event_shifts(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_manage_non_moderated_event_shifts(): void
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

        $event = Event::factory()->create(['organization_id' => $other_org->id]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_manage_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertForbidden();
    }
}
