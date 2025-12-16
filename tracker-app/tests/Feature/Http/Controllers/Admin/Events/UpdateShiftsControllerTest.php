<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateShiftsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_shift_management_form_for_admin(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->count(2)->create(['event_id' => $event->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.shifts');
        $response->assertViewHas('event', $event);
        $response->assertViewHas('shifts');

        $shifts = $response->viewData('shifts');
        $this->assertCount(2, $shifts);
    }

    public function test_invoke_displays_shift_management_form_for_moderator_with_permission(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization, moderator: true)->create();

        $event = Event::factory()->withOrganization($organization)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.shifts');
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
        $response = $this->actingAs($moderator)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_orders_shifts_by_start_time(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $shift_3 = EventShift::factory()->create([
            'event_id' => $event->id,
            'shift_starts_at' => Carbon::now()->addHours(6),
        ]);
        $shift_1 = EventShift::factory()->create([
            'event_id' => $event->id,
            'shift_starts_at' => Carbon::now()->addHours(2),
        ]);
        $shift_2 = EventShift::factory()->create([
            'event_id' => $event->id,
            'shift_starts_at' => Carbon::now()->addHours(4),
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $shifts = $response->viewData('shifts');
        $this->assertEquals($shift_1->id, $shifts->first()->id);
        $this->assertEquals($shift_3->id, $shifts->last()->id);
    }

    public function test_invoke_handles_event_with_no_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.shifts', $event));

        // Assert
        $response->assertOk();
        $shifts = $response->viewData('shifts');
        $this->assertCount(0, $shifts);
    }
}
