<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Troopers\UpdateTrooperMembershipsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for UpdateTrooperMembershipsCommand.
 *
 * Verifies:
 * - Creates new TrooperAssignment when assignment doesn't exist.
 * - Updates existing TrooperAssignment to set is_member = true.
 * - Skips organizations without assignment ID.
 * - Handles multiple organizations in one call.
 */
class UpdateTrooperMembershipsCommandTest extends TestCase
{
    use RefreshDatabase;

    private UpdateTrooperMembershipsCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateTrooperMembershipsCommand();
    }

    public function test_invoke_creates_new_assignment_when_not_exists(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $organizations = [
            '1' => [
                'assignment' => $organization->id,
            ],
        ];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_updates_existing_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        // Create existing assignment with is_member = false
        $existing = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        $organizations = [
            '1' => [
                'assignment' => $organization->id,
            ],
        ];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $existing->refresh();
        $this->assertTrue($existing->is_member);

        // Should only have 1 assignment, not create a duplicate
        $this->assertEquals(1, $trooper->trooper_assignments()->count());
    }

    public function test_invoke_skips_organizations_without_assignment_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $organizations = [
            '1' => [
                'assignment' => null,
            ],
            '2' => [
                // No assignment key at all
            ],
            '3' => [
                'assignment' => '',
            ],
        ];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $this->assertEquals(0, $trooper->trooper_assignments()->count());
    }

    public function test_invoke_handles_multiple_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $organizations = [
            '1' => [
                'assignment' => $org1->id,
            ],
            '2' => [
                'assignment' => null, // Should be skipped
            ],
            '3' => [
                'assignment' => $org2->id,
            ],
            '4' => [
                'assignment' => $org3->id,
            ],
        ];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $this->assertEquals(3, $trooper->trooper_assignments()->count());
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_handles_empty_organizations_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organizations = [];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $this->assertEquals(0, $trooper->trooper_assignments()->count());
    }

    public function test_invoke_uses_trooper_assignments_relationship(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        // Pre-load the relationship
        $trooper->load('trooper_assignments');

        // Create an existing assignment
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        // Reload the relationship
        $trooper->load('trooper_assignments');

        $organizations = [
            '1' => [
                'assignment' => $organization->id,
            ],
        ];

        // Act
        ($this->subject)($trooper, $organizations);

        // Assert
        $trooper->refresh();
        $assignment = $trooper->trooper_assignments->first();
        $this->assertTrue($assignment->is_member);
    }
}
