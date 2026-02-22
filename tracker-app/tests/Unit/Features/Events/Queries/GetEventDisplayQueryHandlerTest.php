<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Features\Events\Queries\GetEventDisplayQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetEventDisplayQueryHandler.
 *
 * Verifies:
 * - Returns event with all related data loaded
 * - Eager loads relationships properly
 * - Assembles event for display
 */
class GetEventDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_event_with_relationships(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertInstanceOf(Event::class, $result);
        $this->assertEquals($event->id, $result->id);
    }

    public function test_invoke_eager_loads_organization(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('organization'));
    }

    public function test_invoke_eager_loads_event_shifts(): void
    {
        // Arrange
        $event = Event::factory()->create();
        EventShift::factory()->for($event)->count(2)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->relationLoaded('event_shifts'));
        $this->assertCount(2, $result->event_shifts);
    }

    public function test_invoke_orders_shifts_by_start_time(): void
    {
        // Arrange
        $event = Event::factory()->create();

        $later_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHours(2),
        ]);
        $earlier_shift = EventShift::factory()->for($event)->create([
            EventShift::SHIFT_STARTS_AT => now()->addHour(),
        ]);

        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals($earlier_shift->id, $result->event_shifts->first()->id);
        $this->assertEquals($later_shift->id, $result->event_shifts->last()->id);
    }

    public function test_invoke_eager_loads_event_troopers(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $shift_result = $result->event_shifts->first();
        $this->assertTrue($shift_result->relationLoaded('event_troopers'));
    }

    public function test_invoke_computes_costume_organizations_for_all_shifts(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift1 = EventShift::factory()->for($event)->create();
        $shift2 = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Create EventTroopers in both shifts
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift1->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift2->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        foreach ($result->event_shifts as $shift)
        {
            foreach ($shift->event_troopers as $event_trooper)
            {
                $this->assertStringContainsString($organization->name, $event_trooper->costume_organizations);
            }
        }
    }

    public function test_invoke_computes_backup_costume_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $costume1 = Costume::factory()->create();
        $costume2 = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume1->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume2->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume1->id,
            EventTrooper::BACKUP_COSTUME_ID => $costume2->id,
        ]);

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $event_trooper = $result->event_shifts->first()->event_troopers->first();
        $this->assertNotNull($event_trooper->backup_costume_organizations);
        $this->assertStringContainsString($organization->name, $event_trooper->backup_costume_organizations);
    }

    public function test_invoke_handles_unattached_costumes(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            // No costumes attached
        ]);

        $query = new GetEventDisplayQuery($event, $trooper);
        $subject = new GetEventDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $event_trooper = $result->event_shifts->first()->event_troopers->first();
        $this->assertStringContainsString('unattached', $event_trooper->costume_organizations);
    }
}
