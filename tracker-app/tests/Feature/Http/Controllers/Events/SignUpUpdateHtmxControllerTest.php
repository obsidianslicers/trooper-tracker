<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_trooper_status(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper->id,
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_updates_event_trooper_costume(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $trooper_costume = TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);
        $event = Event::factory()->withOrganization($costume->organization)->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_organization = EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $costume->organization->id,
            'can_attend' => true
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['costume_id' => $costume->id]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper->id,
            'costume_id' => $costume->id,
        ]);
    }

    public function test_invoke_promotes_standby_trooper_when_cancelling_from_full_shift(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'troopers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        $standby_trooper = Trooper::factory()->asActive()->create();
        $standby_event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $standby_trooper->id,
            'status' => EventTrooperStatus::STAND_BY,
            'is_handler' => false,
            'signed_up_at' => Carbon::now(),
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $standby_event_trooper->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_promotes_earliest_standby_trooper_when_multiple_on_waitlist(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'troopers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        $first_standby = Trooper::factory()->asActive()->create();
        $first_standby_event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $first_standby->id,
            'status' => EventTrooperStatus::STAND_BY,
            'is_handler' => false,
            'signed_up_at' => Carbon::now()->subHours(2),
        ]);

        $second_standby = Trooper::factory()->asActive()->create();
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $second_standby->id,
            'status' => EventTrooperStatus::STAND_BY,
            'is_handler' => false,
            'signed_up_at' => Carbon::now()->subHours(1),
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $first_standby_event_trooper->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_handles_handler_standby_promotion_separately(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'handlers_allowed' => 1,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        $handler = Trooper::factory()->asActive()->asHandler()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $handler->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => true,
        ]);

        $standby_handler = Trooper::factory()->asActive()->asHandler()->create();
        $standby_event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $standby_handler->id,
            'status' => EventTrooperStatus::STAND_BY,
            'is_handler' => true,
            'signed_up_at' => Carbon::now(),
        ]);

        // Act
        $response = $this->actingAs($handler)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $standby_event_trooper->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_does_not_promote_when_shift_not_full(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
            'troopers_allowed' => 10,
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
            'is_handler' => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper->id,
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_denies_access_to_unauthorized_trooper(): void
    {
        // Arrange
        $owner = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $owner->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        $other_trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($other_trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_unauthenticated_user(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_ok_response_on_successful_update(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        $event_shift = EventShift::factory()->create([
            'event_id' => $event->id,
        ]);
        $event_trooper = EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $trooper->id,
            'status' => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->actingAs($trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value]
        );

        // Assert
        $response->assertOk();
        $response->assertSeeText('ok');
    }
}
