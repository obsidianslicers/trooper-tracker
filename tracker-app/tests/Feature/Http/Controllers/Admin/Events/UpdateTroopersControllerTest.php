<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_trooper_roster_management_form_for_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create(['event_id' => $event->id]);

        $trooper = Trooper::factory()->create();
        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.troopers');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('event_shifts');
    }

    public function test_invoke_displays_trooper_roster_for_moderator_with_permission(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization, moderator: true)->create();

        $event = Event::factory()->withOrganization($organization)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.troopers');
    }

    public function test_invoke_denies_access_to_moderator_without_permission(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization_1, member: true)->create();

        $event = Event::factory()->withOrganization($organization_2)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_loads_event_shifts_with_roster_scope(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $shift = EventShift::factory()->create(['event_id' => $event->id]);
        $trooper = Trooper::factory()->create();
        EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertOk();
        $event_shifts = $response->viewData('event_shifts');
        $this->assertCount(1, $event_shifts);
        $this->assertTrue($event_shifts->first()->relationLoaded('event_troopers'));
    }

    public function test_invoke_handles_event_with_no_trooper_registrations(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->create(['event_id' => $event->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertOk();
    }
}
