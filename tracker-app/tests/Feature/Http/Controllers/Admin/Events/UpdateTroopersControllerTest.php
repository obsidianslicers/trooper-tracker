<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_requires_authorization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_displays_troopers_view_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.events.troopers');
    }

    public function test_invoke_passes_event_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertViewHas('event', $event);
    }

    public function test_invoke_passes_event_shifts_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory(2)->for($event)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertViewHas('event_shifts');
        $event_shifts = $response->viewData('event_shifts');
        $this->assertCount(2, $event_shifts);
    }

    public function test_invoke_returns_empty_shifts_when_event_has_no_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertViewHas('event_shifts');
        $event_shifts = $response->viewData('event_shifts');
        $this->assertCount(0, $event_shifts);
    }

    public function test_invoke_includes_event_troopers_in_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $response->assertViewHas('event_shifts');
        $event_shifts = $response->viewData('event_shifts');
        $this->assertCount(1, $event_shifts);
        $this->assertCount(1, $event_shifts->first()->event_troopers);
    }

    public function test_invoke_filters_shifts_by_event_only(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();
        EventShift::factory(2)->for($event1)->create();
        EventShift::factory(3)->for($event2)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event1));

        // Assert
        $response->assertViewHas('event_shifts');
        $event_shifts = $response->viewData('event_shifts');
        // Should only return shifts for the specific event via Query pattern
        $this->assertCount(2, $event_shifts);
        $this->assertTrue($event_shifts->every(fn($shift) => $shift->event_id === $event1->id));
    }

    public function test_invoke_handles_nonexistent_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', 99999));

        // Assert
        $response->assertNotFound();
    }

    public function test_invoke_enriches_event_troopers_with_display_clubs(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.events.troopers', $event));

        // Assert
        $event_shifts = $response->viewData('event_shifts');
        $event_trooper = $event_shifts->first()->event_troopers->first();
        // Verify display_clubs is set by the query handler
        $this->assertIsString($event_trooper->display_clubs);
    }
}
