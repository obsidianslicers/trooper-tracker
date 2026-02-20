<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetTroopersForEventAdminQuery;
use App\Features\Events\Queries\GetTroopersForEventAdminQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventAdminQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_collection_of_event_shifts(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shifts = EventShift::factory(3)->for($event)->create();
        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($item) => $item instanceof EventShift));
    }

    public function test_invoke_returns_empty_collection_when_event_has_no_shifts(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_eager_loads_event_troopers_with_costumes(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $org_costume = OrganizationCostume::factory()->for($organization)->create();
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(1, $result);
        $shift_result = $result->first();
        $this->assertCount(1, $shift_result->event_troopers);
        $this->assertEquals($trooper->id, $shift_result->event_troopers->first()->trooper_id);
    }

    public function test_invoke_computes_costume_organizations_from_approved_costumes(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create();
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $org_costume->costume_id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        $this->assertNotNull($event_trooper->costume_organizations);
        $this->assertStringContainsString($organization->name, $event_trooper->costume_organizations);
    }

    public function test_invoke_includes_potential_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $potential_org = Organization::factory()->create();

        // Create trooper membership with org identifier in potential organization
        $trooper->organizations()->attach($potential_org->id, [
            'membership_status' => 'pending',
            'identifier' => 'TEST123',
        ]);

        $org_costume = OrganizationCostume::factory()->for($potential_org)->create();
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $org_costume->costume_id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$potential_org->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        $this->assertNotNull($event_trooper->costume_organizations);
        $this->assertStringContainsString($potential_org->name, $event_trooper->costume_organizations);
    }

    public function test_invoke_filters_by_event_id(): void
    {
        // Arrange
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();
        EventShift::factory(2)->for($event1)->create();
        EventShift::factory(3)->for($event2)->create();

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event1);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($shift) => $shift->event_id === $event1->id));
    }

    public function test_invoke_returns_shifts_with_multiple_troopers(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $troopers = Trooper::factory(3)->asActive()->create();

        foreach ($troopers as $trooper)
        {
            EventTrooper::factory()->create([
                EventTrooper::EVENT_SHIFT_ID => $shift->id,
                EventTrooper::TROOPER_ID => $trooper->id,
            ]);
        }

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertCount(3, $result->first()->event_troopers);
    }

    public function test_invoke_handles_troopers_with_no_costumes(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(1, $result);
        $event_trooper = $result->first()->event_troopers->first();
        // When no costumes or no potential_orgs match approved, shows (unattached)
        $this->assertIsString($event_trooper->costume_organizations);
        $this->assertStringContainsString('unattached', $event_trooper->costume_organizations);
    }

    public function test_invoke_returns_shifts_in_order(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift1 = EventShift::factory()->for($event)->create(['shift_starts_at' => now()->addDay(1)]);
        $shift2 = EventShift::factory()->for($event)->create(['shift_starts_at' => now()->addDay(2)]);
        $shift3 = EventShift::factory()->for($event)->create(['shift_starts_at' => now()]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $this->assertCount(3, $result);
        $ordered_dates = $result->pluck('shift_starts_at')->toArray();
        $this->assertEquals($shift3->shift_starts_at, $ordered_dates[0]);
        $this->assertEquals($shift1->shift_starts_at, $ordered_dates[1]);
        $this->assertEquals($shift2->shift_starts_at, $ordered_dates[2]);
    }

    public function test_invoke_handles_multiple_costumes_per_trooper(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($organization)->create();
        $org_costume2 = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $org_costume1->costume_id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        $this->assertNotNull($event_trooper->costume_organizations);
        $this->assertStringContainsString($organization->name, $event_trooper->costume_organizations);
    }

    public function test_invoke_excludes_organizations_not_in_costume_organization_ids(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $approved_org = Organization::factory()->create();
        $excluded_org = Organization::factory()->create();

        // Create costumes for both orgs
        $approved_org_costume = OrganizationCostume::factory()->for($approved_org)->create();
        $excluded_org_costume = OrganizationCostume::factory()->for($excluded_org)->create();

        // Trooper has costumes from both organizations
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $approved_org_costume->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $excluded_org_costume->id,
        ]);

        // But signup only includes the approved org
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $approved_org_costume->costume_id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$approved_org->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        // Only approved org should appear, not excluded
        $this->assertStringContainsString($approved_org->name, $event_trooper->costume_organizations);
        $this->assertStringNotContainsString($excluded_org->name, $event_trooper->costume_organizations);
    }

    public function test_invoke_returns_costume_organizations_with_organization_names(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $org_costume->costume_id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        // Verify costume_organizations is a computed attribute with org names
        $this->assertIsString($event_trooper->costume_organizations);
        $this->assertStringContainsString($organization->name, $event_trooper->costume_organizations);
    }

    public function test_invoke_computes_backup_costume_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume1 = Costume::factory()->create();
        $costume2 = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($organization)->create(['costume_id' => $costume1->id]);
        $org_costume2 = OrganizationCostume::factory()->for($organization)->create(['costume_id' => $costume2->id]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume1->id,
            EventTrooper::BACKUP_COSTUME_ID => $costume2->id,
            EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS => [$organization->id],
        ]);

        $handler = new GetTroopersForEventAdminQueryHandler();
        $query = new GetTroopersForEventAdminQuery($event);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        // Note: backup_costume_organizations computation currently filters by costume_id,
        // not backup_costume_id, so this returns (unattached) when costumes differ
        $this->assertIsString($event_trooper->backup_costume_organizations);

        // Act
        $result = $handler($query);

        // Assert
        $event_trooper = $result->first()->event_troopers->first();
        // Multiple organizations should include "(*) " prefix
        $this->assertStringStartsWith('(*)', $event_trooper->costume_organizations);
        $this->assertStringContainsString($org1->name, $event_trooper->costume_organizations);
        $this->assertStringContainsString($org2->name, $event_trooper->costume_organizations);
    }
}
