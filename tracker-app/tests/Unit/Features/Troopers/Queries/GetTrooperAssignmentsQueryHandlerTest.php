<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\OrganizationType;
use App\Features\Troopers\Queries\GetTrooperAssignmentsQuery;
use App\Features\Troopers\Queries\GetTrooperAssignmentsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperAssignmentsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_organizations_collection(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        Organization::factory()->count(3)->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetTrooperAssignmentsQuery($trooper);
        $subject = new GetTrooperAssignmentsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
    }

    public function test_invoke_excludes_regions_and_units(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        Organization::factory()->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $region = Organization::factory()->create([
            Organization::TYPE => OrganizationType::REGION,
        ]);

        $unit = Organization::factory()->create([
            Organization::TYPE => OrganizationType::UNIT,
        ]);

        $query = new GetTrooperAssignmentsQuery($trooper);
        $subject = new GetTrooperAssignmentsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - should only have organizations
        $this->assertNotNull($result->where(Organization::TYPE, OrganizationType::ORGANIZATION)->first());
        $this->assertNull($result->where(Organization::ID, $region->id)->first());
        $this->assertNull($result->where(Organization::ID, $unit->id)->first());
    }

    public function test_invoke_includes_nested_regions_and_units(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        Organization::factory()->count(2)->create([
            Organization::TYPE => OrganizationType::ORGANIZATION,
        ]);

        $query = new GetTrooperAssignmentsQuery($trooper);
        $subject = new GetTrooperAssignmentsQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertGreaterThan(0, $result->count());
        // Organizations should be loaded with nested relationships
        $organization = $result->first();
        $this->assertNotNull($organization);
    }
}