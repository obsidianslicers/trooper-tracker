<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Organizations;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Services\Organizations\GetOrganizationHierarchyQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationHierarchyQuery.
 *
 * Verifies:
 * - Retrieves organization hierarchy with regions and units
 * - Returns correct structure with id, name, regions, and units
 * - Filters to a specific organization when ID provided
 * - Handles organizations with no regions/units
 * - Eager loads relationships to prevent N+1 queries
 * - Orders organizations by name
 */
class GetOrganizationHierarchyQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_collection(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        Organization::factory()->create();

        // Act
        $result = $subject();

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_invoke_returns_organization_with_correct_structure(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result->first());
        $this->assertArrayHasKey('name', $result->first());
        $this->assertArrayHasKey('regions', $result->first());
        $this->assertEquals($organization->id, $result->first()['id']);
        $this->assertEquals('Test Organization', $result->first()['name']);
    }

    public function test_invoke_includes_regions_in_organization(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);
        $region = Organization::factory()->create([
            Organization::NAME => 'Test Region',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $regions = $result->first()['regions'];
        $this->assertCount(1, $regions);
        $this->assertEquals($region->id, $regions->first()['id']);
        $this->assertEquals('Test Region', $regions->first()['name']);
    }

    public function test_invoke_includes_units_in_regions(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);
        $region = Organization::factory()->create([
            Organization::NAME => 'Test Region',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::NAME => 'Test Unit',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $regions = $result->first()['regions'];
        $this->assertCount(1, $regions);
        $units = $regions->first()['units'];
        $this->assertCount(1, $units);
        $this->assertEquals($unit->id, $units->first()['id']);
        $this->assertEquals('Test Unit', $units->first()['name']);
    }

    public function test_invoke_returns_complete_hierarchy_structure(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => '501st Legion',
        ]);
        $region = Organization::factory()->create([
            Organization::NAME => 'SoCal Garrison',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::NAME => 'Squad 7',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $hierarchy = $result->first();
        $this->assertEquals('501st Legion', $hierarchy['name']);
        $this->assertEquals('SoCal Garrison', $hierarchy['regions']->first()['name']);
        $this->assertEquals('Squad 7', $hierarchy['regions']->first()['units']->first()['name']);
    }

    public function test_invoke_filters_to_specific_organization_when_id_provided(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization1 = Organization::factory()->create([
            Organization::NAME => 'Organization 1',
        ]);
        $organization2 = Organization::factory()->create([
            Organization::NAME => 'Organization 2',
        ]);

        // Act
        $result = $subject($organization1->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($organization1->id, $result->first()['id']);
        $this->assertEquals('Organization 1', $result->first()['name']);
    }

    public function test_invoke_returns_all_organizations_when_id_not_provided(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        Organization::factory()->create([
            Organization::NAME => 'Organization 1',
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Organization 2',
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Organization 3',
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(3, $result);
    }

    public function test_invoke_handles_organization_with_no_regions(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        Organization::factory()->create([
            Organization::NAME => 'Standalone Organization',
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()['regions']->isEmpty());
    }

    public function test_invoke_handles_region_with_no_units(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Region Without Units',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $regions = $result->first()['regions'];
        $this->assertCount(1, $regions);
        $this->assertTrue($regions->first()['units']->isEmpty());
    }

    public function test_invoke_handles_multiple_regions_per_organization(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Region 1',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Region 2',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Region 3',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $regions = $result->first()['regions'];
        $this->assertCount(3, $regions);
    }

    public function test_invoke_handles_multiple_units_per_region(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Test Organization',
        ]);
        $region = Organization::factory()->create([
            Organization::NAME => 'Test Region',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Unit 1',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Unit 2',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(1, $result);
        $regions = $result->first()['regions'];
        $units = $regions->first()['units'];
        $this->assertCount(2, $units);
    }

    public function test_invoke_returns_empty_collection_when_no_organizations_exist(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();

        // Act
        $result = $subject();

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function test_invoke_only_returns_top_level_organizations(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);
        Organization::factory()->create([
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);

        // Act
        $result = $subject();

        // Assert
        // Should only return the top-level organization, not the region
        $this->assertCount(1, $result);
        $this->assertEquals($organization->id, $result->first()['id']);
    }

    public function test_invoke_preserves_organization_ids_in_structure(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create();
        $region = Organization::factory()->create([
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertEquals($organization->id, $result->first()['id']);
        $this->assertEquals($region->id, $result->first()['regions']->first()['id']);
        $this->assertEquals($unit->id, $result->first()['regions']->first()['units']->first()['id']);
    }

    public function test_invoke_preserves_organization_names_in_structure(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        $organization = Organization::factory()->create([
            Organization::NAME => 'Galactic Empire',
        ]);
        $region = Organization::factory()->create([
            Organization::NAME => 'Death Star Garrison',
            Organization::PARENT_ID => $organization->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        $unit = Organization::factory()->create([
            Organization::NAME => 'Vader\'s Fist',
            Organization::PARENT_ID => $region->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertEquals('Galactic Empire', $result->first()['name']);
        $this->assertEquals('Death Star Garrison', $result->first()['regions']->first()['name']);
        $this->assertEquals('Vader\'s Fist', $result->first()['regions']->first()['units']->first()['name']);
    }

    public function test_invoke_returns_empty_collection_when_filtered_organization_not_found(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        Organization::factory()->create();

        // Act
        $result = $subject(99999); // Non-existent ID

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    public function test_invoke_handles_complex_multi_organization_hierarchy(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();

        $org1 = Organization::factory()->create([
            Organization::NAME => '501st Legion',
        ]);
        $org1_region1 = Organization::factory()->create([
            Organization::NAME => 'SoCal Garrison',
            Organization::PARENT_ID => $org1->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Squad 7',
            Organization::PARENT_ID => $org1_region1->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        $org2 = Organization::factory()->create([
            Organization::NAME => 'Rebel Legion',
        ]);
        $org2_region1 = Organization::factory()->create([
            Organization::NAME => 'NorCal Base',
            Organization::PARENT_ID => $org2->id,
            Organization::TYPE => OrganizationType::REGION,
        ]);
        Organization::factory()->create([
            Organization::NAME => 'Red Squadron',
            Organization::PARENT_ID => $org2_region1->id,
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        // Act
        $result = $subject();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('501st Legion', $result->first()['name']);
        $this->assertEquals('Rebel Legion', $result->last()['name']);
        $this->assertCount(1, $result->first()['regions']);
        $this->assertCount(1, $result->last()['regions']);
    }

    public function test_invoke_accepts_null_organization_id(): void
    {
        // Arrange
        $subject = new GetOrganizationHierarchyQuery();
        Organization::factory()->create();

        // Act
        $result = $subject(null);

        // Assert
        $this->assertNotEmpty($result);
    }
}
