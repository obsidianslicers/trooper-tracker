<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Services\Troopers\GetTrooperCostumesForOrganizationQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperCostumesForOrganizationQuery.
 *
 * Verifies:
 * - Returns only costumes not already assigned to trooper
 * - Filters costumes by organization
 * - Returns associative array formatted for select dropdowns (id => name)
 * - Orders results alphabetically by costume name
 * - Handles troopers with no costumes
 * - Handles organizations with no costumes
 * - Excludes costumes from other organizations
 */
class GetTrooperCostumesForOrganizationQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_array(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertIsArray($result);
    }

    public function test_invoke_returns_all_costumes_when_trooper_has_none(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);
        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Scout Trooper',
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(2, $result);
    }

    public function test_invoke_excludes_already_assigned_costumes(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $assigned_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);
        $available_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Scout Trooper',
        ]);

        // Assign one costume to trooper
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $assigned_costume->id,
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($available_costume->id, $result);
        $this->assertEquals($available_costume->name, $result[$available_costume->id]);
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $org1_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization1->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);
        $org2_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization2->id,
            OrganizationCostume::NAME => 'Darth Vader',
        ]);

        // Act
        $result = $subject($trooper->id, $organization1->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($org1_costume->id, $result);
        $this->assertEquals($org1_costume->name, $result[$org1_costume->id]);
    }

    public function test_invoke_returns_empty_array_when_no_costumes_available(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_invoke_returns_empty_array_when_all_costumes_assigned(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);

        // Assign the only costume to trooper
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_invoke_orders_costumes_alphabetically_by_name(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Vader',
        ]);
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Scout Trooper',
        ]);
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'AT-AT Driver',
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(3, $result);
        $values = array_values($result);
        $this->assertEquals('AT-AT Driver', $values[0]);
        $this->assertEquals('Scout Trooper', $values[1]);
        $this->assertEquals('Vader', $values[2]);
    }

    public function test_invoke_returns_options_with_label_and_value_keys(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($costume->id, $result);
        $this->assertEquals($costume->name, $result[$costume->id]);
        // Verify it's an associative array (id => name format)
        $keys = array_keys($result);
        $this->assertEquals($costume->id, $keys[0]);
    }

    public function test_invoke_handles_multiple_assigned_costumes(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $assigned1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);
        $assigned2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'Scout Trooper',
        ]);
        $available = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'AT-AT Driver',
        ]);

        // Assign two costumes to trooper
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $assigned1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $assigned2->id,
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($available->id, $result);
        $this->assertEquals($available->name, $result[$available->id]);
    }

    public function test_invoke_excludes_costumes_assigned_to_different_organization(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $org1_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization1->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);
        $org2_costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization2->id,
            OrganizationCostume::NAME => 'Vader',
        ]);

        // Assign costume from organization2 to trooper
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $org2_costume->id,
        ]);

        // Act - query organization1
        $result = $subject($trooper->id, $organization1->id);

        // Assert - org1 costume should still be available
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($org1_costume->id, $result);
        $this->assertEquals($org1_costume->name, $result[$org1_costume->id]);
    }

    public function test_invoke_throws_exception_when_trooper_not_found(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $organization = Organization::factory()->create();
        $non_existent_trooper_id = 999999;

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $subject($non_existent_trooper_id, $organization->id);
    }

    public function test_invoke_handles_large_number_of_costumes(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Create 20 costumes
        for ($i = 1; $i <= 20; $i++)
        {
            OrganizationCostume::factory()->create([
                OrganizationCostume::ORGANIZATION_ID => $organization->id,
                OrganizationCostume::NAME => "Costume {$i}",
            ]);
        }

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(20, $result);
    }

    public function test_invoke_correctly_formats_costume_with_special_characters(): void
    {
        // Arrange
        $subject = new GetTrooperCostumesForOrganizationQuery();
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => "AT-AT Driver (Imperial)",
        ]);

        // Act
        $result = $subject($trooper->id, $organization->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($costume->id, $result);
        $this->assertEquals("AT-AT Driver (Imperial)", $result[$costume->id]);
    }
}
