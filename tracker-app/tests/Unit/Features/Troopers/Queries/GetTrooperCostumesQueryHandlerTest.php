<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperCostumesQueryHandler;
use App\Models\Costume;
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
 * - Returns Costume models filtered by trooper ownership
 * - Eager loads organization_costumes and nested organizations
 * - Computes costume_organizations attribute correctly
 * - Orders costumes alphabetically by name
 * - Returns empty collection when no costumes
 * - Handles multi-org costumes with (*) prefix
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

    public function test_invoke_returns_costume_models(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create(['name' => 'Stormtrooper']);
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Costume::class, $result->first());
        $this->assertEquals($costume->id, $result->first()->id);
        $this->assertEquals('Stormtrooper', $result->first()->name);
    }

    public function test_invoke_eager_loads_organization_costumes_and_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create(['name' => 'Test Garrison']);
        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertTrue($costume_result->relationLoaded('organization_costumes'));
        $this->assertCount(1, $costume_result->organization_costumes);

        $org_costume_result = $costume_result->organization_costumes->first();
        $this->assertTrue($org_costume_result->relationLoaded('organization'));
        $this->assertEquals('Test Garrison', $org_costume_result->organization->name);
    }

    public function test_invoke_computes_costume_organizations_for_single_org(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create(['name' => 'Florida Garrison']);
        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertEquals('Florida Garrison', $costume_result->costume_organizations);
    }

    public function test_invoke_computes_costume_organizations_with_multi_org_prefix(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Georgia Garrison']);
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org1->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org2->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertStringStartsWith('(*) ', $costume_result->costume_organizations);
        $this->assertStringContainsString('Florida Garrison', $costume_result->costume_organizations);
        $this->assertStringContainsString('Georgia Garrison', $costume_result->costume_organizations);
    }

    public function test_invoke_sorts_organization_names_alphabetically(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Zulu Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Alpha Garrison']);
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org1->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org2->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        // Create in Z, A order
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - Alpha should appear before Zulu even though created after
        $costume_result = $result->first();
        $this->assertStringContainsString('Alpha Garrison, Zulu Garrison', $costume_result->costume_organizations);
    }

    public function test_invoke_orders_costumes_by_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume_z = Costume::factory()->create(['name' => 'Zzzz Costume']);
        $costume_a = Costume::factory()->create(['name' => 'Aaaa Costume']);
        $costume_m = Costume::factory()->create(['name' => 'Mmmm Costume']);

        foreach ([$costume_z, $costume_a, $costume_m] as $costume)
        {
            $org_costume = OrganizationCostume::factory()->create([
                OrganizationCostume::ORGANIZATION_ID => $organization->id,
                OrganizationCostume::COSTUME_ID => $costume->id,
            ]);

            TrooperCostume::factory()->create([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
            ]);
        }

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Aaaa Costume', $result[0]->name);
        $this->assertEquals('Mmmm Costume', $result[1]->name);
        $this->assertEquals('Zzzz Costume', $result[2]->name);
    }

    public function test_invoke_filters_by_trooper_id(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume1 = Costume::factory()->create(['name' => 'Trooper 1 Costume']);
        $costume2 = Costume::factory()->create(['name' => 'Trooper 2 Costume']);

        $org_costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume1->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume2->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper1->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper2->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper1);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Trooper 1 Costume', $result->first()->name);
    }

    public function test_invoke_only_loads_organization_costumes_owned_by_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Owned Org']);
        $org2 = Organization::factory()->create(['name' => 'Not Owned Org']);
        $costume = Costume::factory()->create();

        // Create org costumes for both organizations
        $org_costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org1->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $org2->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        // Trooper only owns org_costume1, not org_costume2
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertCount(1, $costume_result->organization_costumes);
        $this->assertEquals('Owned Org', $costume_result->organization_costumes->first()->organization->name);
        $this->assertEquals('Owned Org', $costume_result->costume_organizations);
    }

    public function test_invoke_handles_costume_with_no_organization_costumes(): void
    {
        // Arrange - edge case where costume exists but trooper has no org approvals
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - should return empty since no valid org_costumes are linked
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_multiple_costumes_for_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costumes = Costume::factory(5)->create();

        foreach ($costumes as $costume)
        {
            $org_costume = OrganizationCostume::factory()->create([
                OrganizationCostume::ORGANIZATION_ID => $organization->id,
                OrganizationCostume::COSTUME_ID => $costume->id,
            ]);

            TrooperCostume::factory()->create([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
            ]);
        }

        $query = new GetTrooperCostumesQuery($trooper);
        $subject = new GetTrooperCostumesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(5, $result);
        $this->assertTrue($result->every(fn($item) => $item instanceof Costume));
    }
}
