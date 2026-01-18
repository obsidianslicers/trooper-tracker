<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Enums\OrganizationType;
use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Features\Organizations\Queries\GetOrganizationsQueryHandler;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationsQueryHandler.
 *
 * Verifies:
 * - Returns only organizations of type ORGANIZATION
 * - Orders by name
 * - Excludes regions and units
 */
class GetOrganizationsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_organization_type(): void
    {
        // Arrange
        $organization = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'Test Organization',
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
            Organization::NAME => 'Test Region',
        ]);

        $unit = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
            Organization::NAME => 'Test Unit',
        ]);

        $query = new GetOrganizationsQuery();
        $subject = new GetOrganizationsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($organization->id, $result[0]->id);
    }

    public function test_invoke_orders_by_name(): void
    {
        // Arrange
        $org_b = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'B Organization',
        ]);

        $org_a = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'A Organization',
        ]);

        $org_c = Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::NAME => 'C Organization',
        ]);

        $query = new GetOrganizationsQuery();
        $subject = new GetOrganizationsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals($org_a->id, $result[0]->id);
        $this->assertEquals($org_b->id, $result[1]->id);
        $this->assertEquals($org_c->id, $result[2]->id);
    }
}
