<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventOrganizationScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_pluck_can_attend_returns_organizations_that_can_attend(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($organization->id));
    }

    public function test_scope_pluck_can_attend_excludes_organizations_that_cannot_attend(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => false,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_pluck_can_attend_includes_organizations_with_unlimited_troopers(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // Add multiple troopers to the shift
        EventTrooper::factory()->count(5)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($organization->id));
    }

    public function test_scope_pluck_can_attend_includes_organizations_under_trooper_limit(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 5,
        ]);

        // Add 3 troopers to the shift (under the limit of 5)
        EventTrooper::factory()->count(3)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($organization->id));
    }

    public function test_scope_pluck_can_attend_excludes_organizations_at_trooper_limit(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 3,
        ]);

        // Add exactly 3 troopers to the shift (at the limit)
        EventTrooper::factory()->count(3)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_pluck_can_attend_excludes_organizations_over_trooper_limit(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 2,
        ]);

        // Add 5 troopers to the shift (over the limit of 2)
        EventTrooper::factory()->count(5)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_pluck_can_attend_filters_multiple_organizations(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $organization3 = Organization::factory()->create();
        $event = Event::factory()->for($organization1)->create();
        $event_shift = EventShift::factory()->for($event)->create();
        $costume1 = OrganizationCostume::factory()->for($organization1)->create();
        $costume2 = OrganizationCostume::factory()->for($organization2)->create();

        // Organization 1: can attend, under limit
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization1->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 5,
        ]);
        EventTrooper::factory()->count(2)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume1->id,
        ]);

        // Organization 2: can attend, at limit
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization2->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 2,
        ]);
        EventTrooper::factory()->count(2)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::COSTUME_ID => $costume2->id,
        ]);

        // Organization 3: cannot attend
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization3->id,
            EventOrganization::CAN_ATTEND => false,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($organization1->id));
        $this->assertFalse($result->contains($organization2->id));
        $this->assertFalse($result->contains($organization3->id));
    }

    public function test_scope_pluck_can_attend_returns_empty_collection_when_no_organizations_match(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => false,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_scope_pluck_can_attend_counts_troopers_for_specific_shift_only(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift1 = EventShift::factory()->for($event)->create();
        $event_shift2 = EventShift::factory()->for($event)->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 3,
        ]);

        // Add 2 troopers to shift 1 (under limit)
        EventTrooper::factory()->count(2)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift1->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Add 5 troopers to shift 2 (over limit)
        EventTrooper::factory()->count(5)->create([
            EventTrooper::EVENT_SHIFT_ID => $event_shift2->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        // Act - Check shift 1
        $result_shift1 = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift1);

        // Act - Check shift 2
        $result_shift2 = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift2);

        // Assert - Shift 1 should include organization (under limit)
        $this->assertCount(1, $result_shift1);
        $this->assertTrue($result_shift1->contains($organization->id));

        // Assert - Shift 2 should exclude organization (over limit)
        $this->assertCount(0, $result_shift2);
    }

    public function test_scope_pluck_can_attend_with_zero_trooper_limit_excludes_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 0,
        ]);

        // Act
        $result = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->pluckCanAttend($event_shift);

        // Assert
        $this->assertCount(0, $result);
    }
}
