<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

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

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    private EventTrooper $subject;
    private Trooper $trooper;
    private Event $event;
    private EventShift $shift;
    private Organization $organization;

    public function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->event = Event::factory()->create();
        $this->shift = EventShift::factory()
            ->for($this->event)
            ->create();

        // Create event organization with can_attend=true
        EventOrganization::factory()
            ->for($this->event)
            ->for($this->organization)
            ->create(['can_attend' => true]);

        $this->subject = EventTrooper::factory()
            ->for($this->shift, 'event_shift')
            ->for($this->trooper)
            ->create();
    }

    public function test_saving_assigns_costume_organizations_when_costume_is_dirty(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()
            ->for($this->organization)
            ->for($costume, 'costume')
            ->create();
        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($org_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->costume_id = $costume->id;
        $this->subject->save();

        // Assert
        $this->assertEquals([$this->organization->id], $this->subject->costume_organization_ids);
    }

    public function test_saving_assigns_backup_costume_organizations_when_backup_costume_is_dirty(): void
    {
        // Arrange
        $backup_costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()
            ->for($this->organization)
            ->for($backup_costume, 'costume')
            ->create();
        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($org_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->backup_costume_id = $backup_costume->id;
        $this->subject->save();

        // Assert
        $this->assertEquals([$this->organization->id], $this->subject->backup_costume_organization_ids);
    }

    public function test_saving_assigns_both_costume_organizations(): void
    {
        // Arrange
        $costume = Costume::factory()->create();
        $backup_costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()
            ->for($this->organization)
            ->for($costume, 'costume')
            ->create();
        $backup_org_costume = OrganizationCostume::factory()
            ->for($this->organization)
            ->for($backup_costume, 'costume')
            ->create();

        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($org_costume, 'organization_costume')
            ->create();
        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($backup_org_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->costume_id = $costume->id;
        $this->subject->backup_costume_id = $backup_costume->id;
        $this->subject->save();

        // Assert
        $this->assertEquals([$this->organization->id], $this->subject->costume_organization_ids);
        $this->assertEquals([$this->organization->id], $this->subject->backup_costume_organization_ids);
    }

    public function test_saving_filters_organizations_by_can_attend(): void
    {
        // Arrange
        $allowed_org = Organization::factory()->create();
        $disallowed_org = Organization::factory()->create();

        EventOrganization::factory()
            ->for($this->event)
            ->for($allowed_org)
            ->create(['can_attend' => true]);
        EventOrganization::factory()
            ->for($this->event)
            ->for($disallowed_org)
            ->create(['can_attend' => false]);

        $costume = Costume::factory()->create();
        $allowed_org_costume = OrganizationCostume::factory()
            ->for($allowed_org)
            ->for($costume, 'costume')
            ->create();
        $disallowed_org_costume = OrganizationCostume::factory()
            ->for($disallowed_org)
            ->for($costume, 'costume')
            ->create();

        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($allowed_org_costume, 'organization_costume')
            ->create();
        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($disallowed_org_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->costume_id = $costume->id;
        $this->subject->save();

        // Assert - should only include allowed org
        $this->assertContains($allowed_org->id, $this->subject->costume_organization_ids);
        $this->assertNotContains($disallowed_org->id, $this->subject->costume_organization_ids);
    }

    public function test_saving_filters_organizations_by_trooper_ownership(): void
    {
        // Arrange
        $other_trooper = Trooper::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()
            ->for($this->organization)
            ->for($costume, 'costume')
            ->create();

        // Other trooper owns this costume, not our test trooper
        TrooperCostume::factory()
            ->for($other_trooper, 'trooper')
            ->for($org_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->costume_id = $costume->id;
        $this->subject->save();

        // Assert - should be empty since trooper doesn't own it
        $this->assertEmpty($this->subject->costume_organization_ids);
    }

    public function test_saving_handles_multiple_organizations(): void
    {
        // Arrange
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        EventOrganization::factory()
            ->for($this->event)
            ->for($org1)
            ->create(['can_attend' => true]);
        EventOrganization::factory()
            ->for($this->event)
            ->for($org2)
            ->create(['can_attend' => true]);

        $costume = Costume::factory()->create();
        $org1_costume = OrganizationCostume::factory()
            ->for($org1)
            ->for($costume, 'costume')
            ->create();
        $org2_costume = OrganizationCostume::factory()
            ->for($org2)
            ->for($costume, 'costume')
            ->create();

        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($org1_costume, 'organization_costume')
            ->create();
        TrooperCostume::factory()
            ->for($this->trooper, 'trooper')
            ->for($org2_costume, 'organization_costume')
            ->create();

        // Act
        $this->subject->costume_id = $costume->id;
        $this->subject->save();

        // Assert - should include both organizations
        $this->assertCount(2, $this->subject->costume_organization_ids);
        $this->assertContains($org1->id, $this->subject->costume_organization_ids);
        $this->assertContains($org2->id, $this->subject->costume_organization_ids);
    }

    public function test_saving_does_not_update_when_costume_not_dirty(): void
    {
        // Arrange
        $original_costume_organization_ids = $this->subject->costume_organization_ids ?? [];

        // Act - update a different attribute
        $this->subject->is_handler = true;
        $this->subject->save();

        // Assert - costume_organization_ids should not have changed
        $this->assertEquals($original_costume_organization_ids, $this->subject->costume_organization_ids);
    }

    public function test_saving_handles_null_costume_id(): void
    {
        // Arrange - subject starts with no costume_id

        // Act
        $this->subject->costume_id = null;
        $this->subject->save();

        // Assert
        $this->assertEmpty($this->subject->costume_organization_ids);
    }

    public function test_saving_handles_null_backup_costume_id(): void
    {
        // Arrange - subject starts with no backup_costume_id

        // Act
        $this->subject->backup_costume_id = null;
        $this->subject->save();

        // Assert
        $this->assertEmpty($this->subject->backup_costume_organization_ids);
    }
}
