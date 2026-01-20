<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Enums\OrganizationType;
use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use App\Features\Organizations\Queries\GetOrganizationHierarchyQueryHandler;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationHierarchyQueryHandler.
 *
 * Verifies:
 * - Returns organizations with nested regions and units
 * - Structures data correctly for display
 * - Filters by organization ID when provided
 */
class GetOrganizationHierarchyQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_all_organizations_when_no_id_specified(): void
    {
        // Arrange
        Organization::factory()->count(2)->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetOrganizationHierarchyQuery(null);
        $subject = new GetOrganizationHierarchyQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
    }

    public function test_invoke_returns_single_organization_when_id_specified(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetOrganizationHierarchyQuery($organization->id);
        $subject = new GetOrganizationHierarchyQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($organization->id, $result->first()['id']);
    }

    public function test_invoke_includes_regions_in_hierarchy(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
            Organization::PARENT_ID => $organization->id,
        ]);

        $query = new GetOrganizationHierarchyQuery(null);
        $subject = new GetOrganizationHierarchyQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $first_org = $result->first();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $first_org['regions']);
        $this->assertCount(1, $first_org['regions']);
        $this->assertEquals($region->id, $first_org['regions']->first()['id']);
    }

    public function test_invoke_includes_units_in_hierarchy(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
            Organization::PARENT_ID => $organization->id,
        ]);

        $unit = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
            Organization::PARENT_ID => $region->id,
        ]);

        $query = new GetOrganizationHierarchyQuery(null);
        $subject = new GetOrganizationHierarchyQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $first_org = $result->first();
        $first_region = $first_org['regions']->first();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $first_region['units']);
        $this->assertCount(1, $first_region['units']);
        $this->assertEquals($unit->id, $first_region['units']->first()['id']);
    }

    public function test_invoke_includes_identifier_display(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::IDENTIFIER_DISPLAY => 'TEST',
        ]);

        $query = new GetOrganizationHierarchyQuery(null);
        $subject = new GetOrganizationHierarchyQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $first_org = $result->first();
        $this->assertArrayHasKey('identifier_display', $first_org);
    }
}
