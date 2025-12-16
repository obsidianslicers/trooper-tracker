<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_trooper_with_going_status_when_shift_not_full(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'troopers_allowed' => 10,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_creates_event_trooper_with_standby_status_when_shift_is_full(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'troopers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Fill the shift
        $existing_trooper = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $existing_trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        $new_trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($new_trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $new_trooper->id,
            'status' => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_handles_handler_capacity_separately(): void
    {
        // Arrange
        $handler = Trooper::factory()->asActive()->asHandler()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'handlers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->actingAs($handler)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $handler->id,
            'is_handler' => true,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_sets_handler_to_standby_when_handler_capacity_full(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'handlers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Fill handler capacity
        $existing_handler = Trooper::factory()->asActive()->asHandler()->create();
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $existing_handler->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => true,
        ]);

        $new_handler = Trooper::factory()->asActive()->asHandler()->create();

        // Act
        $response = $this->actingAs($new_handler)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $new_handler->id,
            'status' => EventTrooperStatus::STAND_BY->value,
            'is_handler' => true,
        ]);
    }

    public function test_invoke_records_signed_up_at_timestamp(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $event_trooper = EventTrooper::where('trooper_id', $trooper->id)->first();
        $this->assertNotNull($event_trooper->signed_up_at);
    }

    public function test_invoke_returns_shift_container_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');
        $response->assertViewHas('event');
        $response->assertViewHas('event_shift');
    }

    public function test_invoke_denies_access_to_unauthenticated_user(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_404_for_nonexistent_shift(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => 99999]));

        // Assert
        $response->assertNotFound();
    }
}
