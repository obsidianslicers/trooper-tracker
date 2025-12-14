<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasOrganizationCostumeScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_excluding_filters_out_specified_costume_ids(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();
        $costume3 = OrganizationCostume::factory()->create();
        $costume4 = OrganizationCostume::factory()->create();

        $exclude_ids = [$costume2->id, $costume3->id];

        // Act
        $result = OrganizationCostume::excluding($exclude_ids)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
        $this->assertFalse($result->contains($costume3));
        $this->assertTrue($result->contains($costume4));
    }

    public function test_scope_excluding_with_empty_array_returns_all_costumes(): void
    {
        // Arrange
        OrganizationCostume::factory()->count(3)->create();

        // Act
        $result = OrganizationCostume::excluding([])->get();

        // Assert
        $this->assertCount(3, $result);
    }

    public function test_scope_excluding_accepts_collection(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();
        $costume3 = OrganizationCostume::factory()->create();

        $exclude_collection = collect([$costume2->id, $costume3->id]);

        // Act
        $result = OrganizationCostume::excluding($exclude_collection)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
        $this->assertFalse($result->contains($costume3));
    }

    public function test_scope_excluding_with_single_id(): void
    {
        // Arrange
        $costume1 = OrganizationCostume::factory()->create();
        $costume2 = OrganizationCostume::factory()->create();

        // Act
        $result = OrganizationCostume::excluding([$costume2->id])->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($costume1));
        $this->assertFalse($result->contains($costume2));
    }
}
