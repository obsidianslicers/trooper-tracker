<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\Scopes\HasOrganizationScopes
 */
class HasOrganizationScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_fully_loaded_eager_loads_hierarchy_and_filters_by_top_level(): void
    {
        // Arrange
        $org_b = Organization::factory()->create(['name' => 'Org B', 'type' => OrganizationType::ORGANIZATION]);
        $org_a = Organization::factory()->create(['name' => 'Org A', 'type' => OrganizationType::ORGANIZATION]);
        $region = Organization::factory()->create(['parent_id' => $org_a->id, 'type' => OrganizationType::REGION]);
        $unit = Organization::factory()->create(['parent_id' => $region->id, 'type' => OrganizationType::UNIT]);

        // Act
        $results = Organization::fullyLoaded()->get();

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('Org A', $results[0]->name);
        $this->assertEquals('Org B', $results[1]->name);
        $this->assertTrue($results[0]->relationLoaded('organizations'));
        $this->assertTrue($results[0]->organizations->first()->relationLoaded('organizations'));
    }

    public function test_scope_of_type_regions_returns_only_organizations(): void
    {
        // Arrange
        $organization = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        Organization::factory()->create(['type' => OrganizationType::REGION]);
        Organization::factory()->create(['type' => OrganizationType::UNIT]);

        // Act
        $results = Organization::ofTypeOrganizations()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($organization));
    }

    public function test_scope_of_type_regions_returns_only_regions(): void
    {
        // Arrange
        Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        $region = Organization::factory()->create(['type' => OrganizationType::REGION]);
        Organization::factory()->create(['type' => OrganizationType::UNIT]);

        // Act
        $results = Organization::ofTypeRegions()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($region));
    }

    public function test_scope_of_type_units_returns_only_units(): void
    {
        // Arrange
        Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        Organization::factory()->create(['type' => OrganizationType::REGION]);
        $unit = Organization::factory()->create(['type' => OrganizationType::UNIT]);

        // Act
        $results = Organization::ofTypeUnits()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($unit));
    }

    public function test_scope_with_active_troopers_returns_organizations_with_active_members(): void
    {
        // Arrange
        $active_org = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        $inactive_org = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        $empty_org = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        $trooper1 = Trooper::factory()->create();
        $trooper1->trooper_assignments()->create([
            'organization_id' => $active_org->id,
            'is_member' => true,
        ]);

        $trooper2 = Trooper::factory()->create();
        $trooper2->trooper_assignments()->create([
            'organization_id' => $inactive_org->id,
            'is_member' => false,
        ]);

        // Act
        $results = Organization::withActiveTroopers()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($active_org));
    }

    public function test_scope_with_active_troopers_can_filter_by_a_specific_trooper(): void
    {
        // Arrange
        $org1 = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);
        $org2 = Organization::factory()->create(['type' => OrganizationType::ORGANIZATION]);

        $trooper1 = Trooper::factory()->create();
        $trooper1->trooper_assignments()->create([
            'organization_id' => $org1->id,
            'is_member' => true,
        ]);

        $trooper2 = Trooper::factory()->create();
        $trooper2->trooper_assignments()->create([
            'organization_id' => $org2->id,
            'is_member' => true,
        ]);

        // Act
        $results = Organization::withActiveTroopers($trooper1->id)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($org1));
    }
}

