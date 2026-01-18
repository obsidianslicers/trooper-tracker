<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperCostumesQueryHandler;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperCostumesQueryHandler.
 *
 * Verifies:
 * - Returns trooper's costumes
 * - Eager loads relationships
 * - Returns empty collection when no costumes
 */
class GetTrooperCostumesQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_empty_collection_when_no_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_trooper_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $org_costume->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($trooper_costume->id, $result[0]->id);
    }

    public function test_invoke_eager_loads_relationships(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Test Costume',
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $org_costume->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result[0]->relationLoaded('organization_costume'));
        $this->assertEquals('Test Costume', $result[0]->organization_costume->name);
        $this->assertEquals($organization->id, $result[0]->organization_costume->organization->id);
    }
}
