<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\Events\UpdateEventOrganizationsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for UpdateEventOrganizationsCommand.
 *
 * Verifies:
 * - Synchronizes event organization relationships with provided data.
 * - Sets can_attend permissions for each organization.
 * - Updates trooper and handler limits for organizations.
 * - Defaults to can_attend=false and null limits for organizations not in data.
 * - Merges provided data with existing organizations.
 * - Persists changes to event_organizations pivot table.
 */
class UpdateEventOrganizationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private UpdateEventOrganizationsCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateEventOrganizationsCommand();
    }

    public function test_invoke_updates_organization_can_attend_permission(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();

        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => false,
        ]);

        $data = [
            $organization->id => [
                EventOrganization::CAN_ATTEND => true,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $pivot = $event->organizations()
            ->where('organization_id', $organization->id)
            ->first()
            ->pivot;

        $this->assertTrue((bool) $pivot->can_attend);
    }

    public function test_invoke_updates_organization_trooper_limits(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();

        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
            EventOrganization::HANDLERS_ALLOWED => null,
        ]);

        $data = [
            $organization->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 15,
                EventOrganization::HANDLERS_ALLOWED => 5,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $pivot = $event->organizations()
            ->where('organization_id', $organization->id)
            ->first()
            ->pivot;

        $this->assertEquals(15, $pivot->troopers_allowed);
        $this->assertEquals(5, $pivot->handlers_allowed);
    }

    public function test_invoke_sets_default_values_for_organizations_not_in_data(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $event->organizations()->attach($org1->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);

        $event->organizations()->attach($org2->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 20,
        ]);

        // Only update org1, org2 should be set to defaults
        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 15,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();

        // org1 should have new values
        $org1_pivot = $event->organizations()
            ->where('organization_id', $org1->id)
            ->first()
            ->pivot;
        $this->assertTrue((bool) $org1_pivot->can_attend);
        $this->assertEquals(15, $org1_pivot->troopers_allowed);

        // org2 should be reset to defaults
        $org2_pivot = $event->organizations()
            ->where('organization_id', $org2->id)
            ->first()
            ->pivot;
        $this->assertFalse((bool) $org2_pivot->can_attend);
        $this->assertNull($org2_pivot->troopers_allowed);
        $this->assertNull($org2_pivot->handlers_allowed);
    }

    public function test_invoke_merges_data_with_existing_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $event->organizations()->attach([
            $org1->id => [
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
            ],
            $org2->id => [
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
            ],
            $org3->id => [
                EventOrganization::CAN_ATTEND => false,
                EventOrganization::TROOPERS_ALLOWED => null,
            ],
        ]);

        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 10,
            ],
            $org2->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 20,
            ],
            // org3 not in data, should get defaults
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertCount(3, $event->organizations);

        $org1_pivot = $event->organizations()->find($org1->id)->pivot;
        $this->assertTrue((bool) $org1_pivot->can_attend);
        $this->assertEquals(10, $org1_pivot->troopers_allowed);

        $org2_pivot = $event->organizations()->find($org2->id)->pivot;
        $this->assertTrue((bool) $org2_pivot->can_attend);
        $this->assertEquals(20, $org2_pivot->troopers_allowed);

        $org3_pivot = $event->organizations()->find($org3->id)->pivot;
        $this->assertFalse((bool) $org3_pivot->can_attend);
        $this->assertNull($org3_pivot->troopers_allowed);
    }

    public function test_invoke_handles_empty_data_array(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();

        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);

        $data = [];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $pivot = $event->organizations()
            ->where('organization_id', $organization->id)
            ->first()
            ->pivot;

        // Should be reset to defaults since not in data
        $this->assertFalse((bool) $pivot->can_attend);
        $this->assertNull($pivot->troopers_allowed);
        $this->assertNull($pivot->handlers_allowed);
    }

    public function test_invoke_updates_multiple_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $event->organizations()->attach([
            $org1->id => [EventOrganization::CAN_ATTEND => false],
            $org2->id => [EventOrganization::CAN_ATTEND => false],
            $org3->id => [EventOrganization::CAN_ATTEND => false],
        ]);

        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 5,
                EventOrganization::HANDLERS_ALLOWED => 2,
            ],
            $org2->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 10,
                EventOrganization::HANDLERS_ALLOWED => 3,
            ],
            $org3->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 15,
                EventOrganization::HANDLERS_ALLOWED => 5,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();

        $org1_pivot = $event->organizations()->find($org1->id)->pivot;
        $this->assertTrue((bool) $org1_pivot->can_attend);
        $this->assertEquals(5, $org1_pivot->troopers_allowed);
        $this->assertEquals(2, $org1_pivot->handlers_allowed);

        $org2_pivot = $event->organizations()->find($org2->id)->pivot;
        $this->assertTrue((bool) $org2_pivot->can_attend);
        $this->assertEquals(10, $org2_pivot->troopers_allowed);
        $this->assertEquals(3, $org2_pivot->handlers_allowed);

        $org3_pivot = $event->organizations()->find($org3->id)->pivot;
        $this->assertTrue((bool) $org3_pivot->can_attend);
        $this->assertEquals(15, $org3_pivot->troopers_allowed);
        $this->assertEquals(5, $org3_pivot->handlers_allowed);
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();

        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => false,
        ]);

        $data = [
            $organization->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 25,
                EventOrganization::HANDLERS_ALLOWED => 8,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $this->assertDatabaseHas('tt_event_organizations', [
            'event_id' => $event->id,
            'organization_id' => $organization->id,
            'can_attend' => true,
            'troopers_allowed' => 25,
            'handlers_allowed' => 8,
        ]);
    }

    public function test_invoke_does_not_detach_existing_organizations(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $event->organizations()->attach([
            $org1->id => [EventOrganization::CAN_ATTEND => false],
            $org2->id => [EventOrganization::CAN_ATTEND => false],
        ]);

        $initial_count = $event->organizations()->count();

        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
            ],
        ];

        // Act
        ($this->subject)($event, $data);

        // Assert
        $event->refresh();
        $this->assertEquals($initial_count, $event->organizations()->count());
        $this->assertTrue($event->organizations()->pluck('tt_organizations.id')->contains($org1->id));
        $this->assertTrue($event->organizations()->pluck('tt_organizations.id')->contains($org2->id));
    }
}
