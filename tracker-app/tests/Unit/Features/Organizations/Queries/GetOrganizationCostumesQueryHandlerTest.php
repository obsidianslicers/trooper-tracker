<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Enums\OrganizationType;
use App\Features\Organizations\Queries\GetOrganizationCostumesQuery;
use App\Features\Organizations\Queries\GetOrganizationCostumesQueryHandler;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationCostumesQueryHandler.
 *
 * Verifies:
 * - Returns organizations with costumes
 * - Filters by organization IDs when provided
 * - Returns only organization type
 * - Maps data correctly
 */
class GetOrganizationCostumesQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_with_costumes(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Test Organization',
        ]);

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Costume A',
        ]);

        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Costume B',
        ]);

        $query = new GetOrganizationCostumesQuery();
        $subject = new GetOrganizationCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($organization->id, $result[0]['id']);
        $this->assertEquals('Test Organization', $result[0]['name']);
        $this->assertCount(2, $result[0]['organization_costumes']);
        $this->assertEquals($costume1->id, $result[0]['organization_costumes'][0]['id']);
        $this->assertEquals('Costume A', $result[0]['organization_costumes'][0]['name']);
    }

    public function test_invoke_filters_by_organization_ids(): void
    {
        // Arrange
        $org1 = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $org2 = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetOrganizationCostumesQuery([$org1->id]);
        $subject = new GetOrganizationCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($org1->id, $result[0]['id']);
    }

    public function test_invoke_excludes_regions_and_units(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
        ]);

        $query = new GetOrganizationCostumesQuery();
        $subject = new GetOrganizationCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($organization->id, $result[0]['id']);
    }
}
