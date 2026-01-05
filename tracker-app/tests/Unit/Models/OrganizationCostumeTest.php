<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\OrganizationCostume
 */
class OrganizationCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_attribute_returns_name_with_organization_when_loaded(): void
    {
        // Arrange
        $organization = Organization::factory()->create(['name' => '501st Legion']);
        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        // Load the relationship
        $costume->load('organization');

        // Act
        $full_name = $costume->full_name;

        // Assert
        $this->assertSame('(501st Legion) Stormtrooper', $full_name);
    }

    public function test_full_name_attribute_returns_name_only_when_organization_not_loaded(): void
    {
        // Arrange
        $organization = Organization::factory()->create(['name' => '501st Legion']);
        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        // Act
        $full_name = $costume->full_name;

        // Assert
        $this->assertSame('Stormtrooper', $full_name);
    }
}
