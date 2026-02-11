<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignUpUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event_trooper = EventTrooper::factory()->create();

        // Act
        $response = $this->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbids_non_owner_updates(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = $this->createEventTrooper($event_shift, $trooper, EventTrooperStatus::GOING);

        // Act
        $response = $this->actingAs($other_trooper)->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_updates_status_for_owner(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event_shift = EventShift::factory()->create();
        $event_trooper = $this->createEventTrooper($event_shift, $trooper, EventTrooperStatus::GOING);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_updates_costume_for_owner(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        [$event_shift, $organization] = $this->createEventShiftWithOrganization();
        $costume = $this->createCostumeForTrooper($trooper, $organization);
        $event_trooper = $this->createEventTrooper($event_shift, $trooper, EventTrooperStatus::GOING);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_updates_backup_costume_for_owner(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        [$event_shift, $organization] = $this->createEventShiftWithOrganization();
        $backup_costume = $this->createCostumeForTrooper($trooper, $organization);
        $event_trooper = $this->createEventTrooper($event_shift, $trooper, EventTrooperStatus::GOING);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::BACKUP_COSTUME_ID => $backup_costume->id,
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::BACKUP_COSTUME_ID => $backup_costume->id,
        ]);
    }

    public function test_invoke_promotes_standby_when_full_and_cancelled(): void
    {
        // Arrange
        Mail::fake();

        $trooper = Trooper::factory()->asActive()->create();
        $standby_trooper = Trooper::factory()->asActive()->create();
        $event_shift = $this->createEventShiftWithCapacity(1);
        $event_trooper = $this->createEventTrooper($event_shift, $trooper, EventTrooperStatus::GOING);
        $standby_event_trooper = $this->createEventTrooper(
            $event_shift,
            $standby_trooper,
            EventTrooperStatus::STAND_BY
        );

        // Act
        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', $event_trooper), [
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $standby_event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    /**
     * @return array{0: EventShift, 1: Organization}
     */
    private function createEventShiftWithOrganization(): array
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([Event::ORGANIZATION_ID => $organization->id]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        $event_shift = EventShift::factory()->withEvent($event)->create();

        return [$event_shift, $organization];
    }

    private function createEventShiftWithCapacity(int $troopers_allowed): EventShift
    {
        $event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => $troopers_allowed,
        ]);

        return EventShift::factory()->withEvent($event)->create();
    }

    private function createEventTrooper(
        EventShift $event_shift,
        Trooper $trooper,
        EventTrooperStatus $status
    ): EventTrooper {
        return EventTrooper::factory()->create([
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::STATUS => $status->value,
            EventTrooper::IS_HANDLER => false,
        ]);
    }

    private function createCostumeForTrooper(
        Trooper $trooper,
        Organization $organization
    ): OrganizationCostume {
        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        return $costume;
    }
}
