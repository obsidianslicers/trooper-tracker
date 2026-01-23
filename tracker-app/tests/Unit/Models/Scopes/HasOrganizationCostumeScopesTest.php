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
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasOrganizationCostumeScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_excluding_filters_out_specified_costume_ids(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();
        $costume3 = OrganizationCostume::factory()->create();
        $costume4 = OrganizationCostume::factory()->create();

        $exclude_ids = [$costume2->id, $costume3->id];

        // Act
        $result = OrganizationCostume::excluding($exclude_ids)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
        $this->assertFalse($result->contains($costume3));
        $this->assertTrue($result->contains($costume4));
    }

    public function test_scope_excluding_with_empty_array_returns_all_costumes(): void
    {
        // Arrange
        OrganizationCostume::factory()->count(3)->create();

        // Act
        $result = OrganizationCostume::excluding([])->get();

        // Assert
        $this->assertCount(3, $result);
    }

    public function test_scope_excluding_accepts_collection(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();
        $costume3 = OrganizationCostume::factory()->create();

        $exclude_collection = collect([$costume2->id, $costume3->id]);

        // Act
        $result = OrganizationCostume::excluding($exclude_collection)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
        $this->assertFalse($result->contains($costume3));
    }

    public function test_scope_excluding_with_single_id(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();

        // Act
        $result = OrganizationCostume::excluding([$costume2->id])->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
    }

    public function test_scope_for_event_shift_returns_costumes_for_trooper_in_allowed_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [$organization->id])->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($costume->id, $result->first()->id);
    }

    public function test_scope_for_event_shift_excludes_costumes_from_organizations_that_cannot_attend(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [])->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_for_event_shift_excludes_costumes_from_organizations_at_trooper_limit(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [])->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_for_event_shift_includes_costumes_from_organizations_under_trooper_limit(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume->id,
        ]);

        EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
            'troopers_allowed' => 5,
        ]);

        // Add one trooper to the shift (under limit)
        EventTrooper::factory()->create([
            'event_shift_id' => $event_shift->id,
            'trooper_id' => $other_trooper->id,
            'costume_id' => $costume->id,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [$organization->id])->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($costume->id, $result->first()->id);
    }

    public function test_scope_for_event_shift_excludes_costumes_not_owned_by_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume = OrganizationCostume::factory()->for($organization)->create();

        // Costume owned by other_trooper, not by trooper
        TrooperCostume::factory()->create([
            'trooper_id' => $other_trooper->id,
            'costume_id' => $costume->id,
        ]);

        EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
            'troopers_allowed' => null,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [$organization->id])->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_scope_for_event_shift_includes_multiple_costumes_for_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume1 = OrganizationCostume::factory()->for($organization)->create();
        $costume2 = OrganizationCostume::factory()->for($organization)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume1->id,
        ]);

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume2->id,
        ]);

        EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
            'troopers_allowed' => null,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [$organization->id])->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $costume1->id));
        $this->assertTrue($result->contains('id', $costume2->id));
    }

    public function test_scope_for_event_shift_filters_by_multiple_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization1)->create();
        $event_shift = EventShift::factory()->for($event)->create();

        $costume1 = OrganizationCostume::factory()->for($organization1)->create();
        $costume2 = OrganizationCostume::factory()->for($organization2)->create();

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume1->id,
        ]);

        TrooperCostume::factory()->create([
            'trooper_id' => $trooper->id,
            'costume_id' => $costume2->id,
        ]);

        // Only organization1 can attend
        EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $organization1->id,
            'can_attend' => true,
            'troopers_allowed' => null,
        ]);

        EventOrganization::factory()->create([
            'event_id' => $event->id,
            'organization_id' => $organization2->id,
            'can_attend' => false,
        ]);

        // Act
        $result = OrganizationCostume::forEventShift($event_shift, $trooper, [$organization1->id])->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($costume1->id, $result->first()->id);
    }
}
