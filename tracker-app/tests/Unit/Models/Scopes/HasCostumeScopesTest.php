<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class HasCostumeScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_trooper_returns_empty_when_trooper_has_no_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $costume = Costume::factory()->create();

        // Act
        $result = Costume::forTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_for_trooper_returns_costumes_approved_by_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($costume->id, $result->first()->id);
    }

    public function test_for_trooper_eager_loads_organization_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('organization_costumes'));
        $this->assertCount(1, $result->organization_costumes);
    }

    public function test_for_trooper_includes_organization_details_in_eager_load(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create(['name' => 'Test Organization']);
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id)->first();
        $loaded_org_costume = $result->organization_costumes->first();

        // Assert
        $this->assertTrue($loaded_org_costume->relationLoaded('organization'));
        $this->assertEquals('Test Organization', $loaded_org_costume->organization->name);
    }

    public function test_for_trooper_filters_by_organization_ids(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Organization 1']);
        $org2 = Organization::factory()->create(['name' => 'Organization 2']);
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($org1)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($org2)->create([
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

        // Act
        $result = Costume::forTrooper($trooper->id, [$org1->id])->first();

        // Assert
        $this->assertCount(1, $result->organization_costumes);
        $this->assertEquals($org1->id, $result->organization_costumes->first()->organization_id);
    }

    public function test_for_trooper_filters_by_organization_ids_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Organization 1']);
        $org2 = Organization::factory()->create(['name' => 'Organization 2']);
        $org3 = Organization::factory()->create(['name' => 'Organization 3']);
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($org1)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($org2)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume3 = OrganizationCostume::factory()->for($org3)->create([
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
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume3->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id, [$org1->id, $org2->id])->first();

        // Assert
        $this->assertCount(2, $result->organization_costumes);
        $org_ids = $result->organization_costumes->pluck('organization_id')->toArray();
        $this->assertContains($org1->id, $org_ids);
        $this->assertContains($org2->id, $org_ids);
        $this->assertNotContains($org3->id, $org_ids);
    }

    public function test_for_trooper_filters_by_organization_ids_collection(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create(['name' => 'Organization 1']);
        $org2 = Organization::factory()->create(['name' => 'Organization 2']);
        $org3 = Organization::factory()->create(['name' => 'Organization 3']);
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($org1)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($org2)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume3 = OrganizationCostume::factory()->for($org3)->create([
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
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume3->id,
        ]);

        // Act - pass as Collection
        $org_ids = collect([$org1->id, $org2->id]);
        $result = Costume::forTrooper($trooper->id, $org_ids)->first();

        // Assert
        $this->assertCount(2, $result->organization_costumes);
        $returned_org_ids = $result->organization_costumes->pluck('organization_id')->toArray();
        $this->assertContains($org1->id, $returned_org_ids);
        $this->assertContains($org2->id, $returned_org_ids);
        $this->assertNotContains($org3->id, $returned_org_ids);
    }

    public function test_for_trooper_returns_multiple_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume1 = Costume::factory()->create();
        $costume2 = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume1->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume2->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(2, $result);
        $costume_ids = $result->pluck('id')->toArray();
        $this->assertContains($costume1->id, $costume_ids);
        $this->assertContains($costume2->id, $costume_ids);
    }

    public function test_for_trooper_excludes_costumes_from_other_troopers(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper2->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper1->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_for_trooper_includes_only_approved_organization_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()->for($org1)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);
        $org_costume2 = OrganizationCostume::factory()->for($org2)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        // Trooper only approves the first organization costume
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);

        // Act
        $result = Costume::forTrooper($trooper->id)->first();

        // Assert
        $this->assertCount(1, $result->organization_costumes);
        $this->assertEquals($org_costume1->id, $result->organization_costumes->first()->id);
    }

    public function test_for_trooper_returns_costume_with_empty_collection_when_organization_filter_excludes_all_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($org1)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act - query with organizations that don't include the costume
        $result = Costume::forTrooper($trooper->id, [$org2->id, $org3->id])->first();

        // Assert - costume is still returned but with empty organization_costumes collection
        $this->assertNotNull($result);
        $this->assertEquals($costume->id, $result->id);
        $this->assertCount(0, $result->organization_costumes);
    }

    public function test_for_trooper_allows_method_chaining(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create(['name' => 'Test Costume']);

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Act - chain where clause after forTrooper
        $result = Costume::forTrooper($trooper->id)
            ->where(Costume::NAME, 'Test Costume')
            ->first();

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('Test Costume', $result->name);
    }

    public function test_for_trooper_with_soft_deleted_trooper_costumes(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->for($organization)->create([
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $trooper_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
        ]);

        // Soft-delete the trooper costume approval
        $trooper_costume->delete();

        // Act
        $result = Costume::forTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}
