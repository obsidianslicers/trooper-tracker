<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Troopers\GetTrooperOrganizationMembershipsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for GetTrooperOrganizationMembershipsQuery.
 *
 * Verifies:
 * - Returns all top-level organizations ordered by name.
 * - Enriches organizations with trooper's specific assignments.
 * - Matches assignments based on node_path hierarchy.
 * - Handles troopers with no assignments.
 * - Handles organizations with no matching assignments.
 */
class GetTrooperOrganizationMembershipsQueryTest extends TestCase
{
    use RefreshDatabase;

    private GetTrooperOrganizationMembershipsQuery $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GetTrooperOrganizationMembershipsQuery();
    }

    public function test_invoke_returns_all_top_level_organizations_ordered_by_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        Organization::factory()->create([Organization::NAME => 'Zeta Club']);
        Organization::factory()->create([Organization::NAME => 'Alpha Club']);
        Organization::factory()->create([Organization::NAME => 'Beta Club']);

        // Act
        $result = ($this->subject)($trooper);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Alpha Club', $result[0]->name);
        $this->assertEquals('Beta Club', $result[1]->name);
        $this->assertEquals('Zeta Club', $result[2]->name);
    }

    public function test_invoke_enriches_organization_with_trooper_assignment(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([
            Organization::NAME => 'Test Club',
            Organization::NODE_PATH => '/1/',
        ]);

        $region = Organization::factory()->asRegion()->create([
            Organization::PARENT_ID => $club->id,
            Organization::NAME => 'Test Region',
            Organization::NODE_PATH => '/1/2/',
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $result = ($this->subject)($trooper);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Test Club', $result[0]->name);
        $this->assertTrue(isset($result[0]->assignment));
        $this->assertEquals('Test Region', $result[0]->assignment->name);
    }

    public function test_invoke_matches_assignment_by_node_path_hierarchy(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([
            Organization::NAME => 'Main Club',
            Organization::NODE_PATH => '/1/',
        ]);

        $region = Organization::factory()->create([
            Organization::PARENT_ID => $club->id,
            Organization::NAME => 'Main Region',
            Organization::NODE_PATH => '/1/2/',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
            Organization::NAME => 'Main Unit',
            Organization::NODE_PATH => '/1/2/3/',
            Organization::TYPE => \App\Enums\OrganizationType::UNIT,
        ]);

        // Trooper assigned to unit - should match the club's hierarchy
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $unit->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $result = ($this->subject)($trooper);

        // Assert: Should only return top-level organization (Main Club)
        $this->assertCount(1, $result);
        $this->assertEquals('Main Club', $result[0]->name);
        $this->assertTrue(isset($result[0]->assignment));
        $this->assertEquals('Main Unit', $result[0]->assignment->name);
        $this->assertStringContainsString('3:', $result[0]->assignment->node_path);
    }

    public function test_invoke_does_not_include_non_member_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([
            Organization::NAME => 'Test Club',
            Organization::NODE_PATH => '/1/',
        ]);

        $region = Organization::factory()->create([
            Organization::PARENT_ID => $club->id,
            Organization::NAME => 'Test Region',
            Organization::NODE_PATH => '/1/2/',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        // Create assignment with is_member = false (handler)
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => false,
        ]);

        // Act
        $result = ($this->subject)($trooper);

        // Assert: Should only return top-level organization
        $this->assertCount(1, $result);
        $this->assertEquals('Test Club', $result[0]->name);
        $this->assertFalse(isset($result[0]->assignment));
    }

    public function test_invoke_handles_trooper_with_no_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        Organization::factory()->create([Organization::NAME => 'Club A']);
        Organization::factory()->create([Organization::NAME => 'Club B']);

        // Act
        $result = ($this->subject)($trooper);

        // Assert
        $this->assertCount(2, $result);
        $this->assertFalse(isset($result[0]->assignment));
        $this->assertFalse(isset($result[1]->assignment));
    }

    public function test_invoke_handles_multiple_organizations_with_mixed_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club1 = Organization::factory()->create([
            Organization::NAME => 'Club One',
            Organization::NODE_PATH => '/1/',
        ]);

        $region1 = Organization::factory()->create([
            Organization::PARENT_ID => $club1->id,
            Organization::NAME => 'Region One',
            Organization::NODE_PATH => '/1/2/',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        $club2 = Organization::factory()->create([
            Organization::NAME => 'Club Two',
            Organization::NODE_PATH => '/3/',
        ]);

        // Trooper assigned to club1's region
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $result = ($this->subject)($trooper);

        // Assert
        $this->assertCount(2, $result);

        // Club One should have assignment
        $clubOne = $result->firstWhere('name', 'Club One');
        $this->assertNotNull($clubOne);
        $this->assertTrue(isset($clubOne->assignment));
        $this->assertEquals('Region One', $clubOne->assignment->name);

        // Club Two should not have assignment
        $clubTwo = $result->firstWhere('name', 'Club Two');
        $this->assertNotNull($clubTwo);
        $this->assertFalse(isset($clubTwo->assignment));
    }

    public function test_invoke_only_returns_top_level_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([Organization::NAME => 'Main Club']);

        // Create region (should not be returned)
        Organization::factory()->create([
            Organization::PARENT_ID => $club->id,
            Organization::NAME => 'Sub Region',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        // Act
        $result = ($this->subject)($trooper);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Main Club', $result[0]->name);
    }
}
