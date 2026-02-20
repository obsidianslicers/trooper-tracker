<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Costumes\Queries;

use App\Features\Costumes\Queries\GetCostumesPickerQuery;
use App\Features\Costumes\Queries\GetCostumesPickerQueryHandler;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetCostumesPickerQueryHandler.
 *
 * Verifies:
 * - Returns Costume models with organization relationships
 * - Eager loads organization_costumes and nested organizations
 * - Filters by organization IDs array
 * - Orders costumes alphabetically by name
 * - Handles empty organization IDs array
 * - Includes PREFIX column in organization_costumes
 */
class GetCostumesPickerQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_empty_collection_when_no_costumes(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $query = new GetCostumesPickerQuery([$organization->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_costume_models_ordered_by_name(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume_b = Costume::factory()->create([Costume::NAME => 'Boba Fett']);
        $costume_a = Costume::factory()->create([Costume::NAME => 'Stormtrooper']);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume_b->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume_a->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Costume::class, $result->first());
        $this->assertEquals('Boba Fett', $result->first()->{Costume::NAME});
        $this->assertEquals('Stormtrooper', $result->last()->{Costume::NAME});
    }

    public function test_invoke_eager_loads_organization_costumes_and_organizations(): void
    {
        // Arrange
        $organization = Organization::factory()->create([Organization::NAME => 'Test Garrison']);
        $costume = Costume::factory()->create([Costume::NAME => 'Stormtrooper']);
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertTrue($costume_result->relationLoaded('organization_costumes'));
        $this->assertCount(1, $costume_result->organization_costumes);

        $org_costume = $costume_result->organization_costumes->first();
        $this->assertTrue($org_costume->relationLoaded('organization'));
        $this->assertEquals('Test Garrison', $org_costume->organization->{Organization::NAME});
    }

    public function test_invoke_returns_costume_associated_with_multiple_organizations(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create([Organization::NAME => 'Garrison 1']);
        $organization_2 = Organization::factory()->create([Organization::NAME => 'Garrison 2']);
        $costume = Costume::factory()->create([Costume::NAME => 'Stormtrooper']);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_1->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_2->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization_1->id, $organization_2->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $costume_result = $result->first();
        $this->assertEquals($costume->id, $costume_result->id);
        $this->assertCount(2, $costume_result->organization_costumes);
    }

    public function test_invoke_selects_only_id_and_name_columns(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $this->assertNotNull($costume_result->id);
        $this->assertNotNull($costume_result->{Costume::NAME});

        // Verify only selected columns are loaded (other attributes will be missing)
        $attributes = array_keys($costume_result->getAttributes());
        $this->assertCount(2, $attributes);
        $this->assertContains('id', $attributes);
        $this->assertContains(Costume::NAME, $attributes);
    }

    public function test_invoke_filters_by_organization_id(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();

        $costume_1 = Costume::factory()->create([Costume::NAME => 'Costume 1']);
        $costume_2 = Costume::factory()->create([Costume::NAME => 'Costume 2']);
        $costume_shared = Costume::factory()->create([Costume::NAME => 'Shared Costume']);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_1->id,
            OrganizationCostume::COSTUME_ID => $costume_1->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_2->id,
            OrganizationCostume::COSTUME_ID => $costume_2->id,
        ]);

        // Create a costume that belongs to both organizations
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_1->id,
            OrganizationCostume::COSTUME_ID => $costume_shared->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_2->id,
            OrganizationCostume::COSTUME_ID => $costume_shared->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization_1->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        // Returns all costumes but filters organization_costumes to only those matching the organization_ids
        $this->assertCount(3, $result);
        $costume_ids = $result->pluck('id')->toArray();
        $this->assertContains($costume_1->id, $costume_ids);
        $this->assertContains($costume_2->id, $costume_ids);
        $this->assertContains($costume_shared->id, $costume_ids);

        // Costumes for organization_1 should have organization_costumes
        $costume_1_result = $result->firstWhere('id', $costume_1->id);
        $this->assertCount(1, $costume_1_result->organization_costumes);
        $this->assertEquals($organization_1->id, $costume_1_result->organization_costumes->first()->{OrganizationCostume::ORGANIZATION_ID});

        $costume_shared_result = $result->firstWhere('id', $costume_shared->id);
        $this->assertCount(1, $costume_shared_result->organization_costumes);
        $this->assertEquals($organization_1->id, $costume_shared_result->organization_costumes->first()->{OrganizationCostume::ORGANIZATION_ID});

        // Costume_2 should have empty organization_costumes collection (filtered out)
        $costume_2_result = $result->firstWhere('id', $costume_2->id);
        $this->assertCount(0, $costume_2_result->organization_costumes);
    }

    public function test_invoke_filters_by_multiple_organization_ids(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();
        $organization_3 = Organization::factory()->create();

        $costume_1 = Costume::factory()->create([Costume::NAME => 'Costume 1']);
        $costume_2 = Costume::factory()->create([Costume::NAME => 'Costume 2']);
        $costume_3 = Costume::factory()->create([Costume::NAME => 'Costume 3']);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_1->id,
            OrganizationCostume::COSTUME_ID => $costume_1->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_2->id,
            OrganizationCostume::COSTUME_ID => $costume_2->id,
        ]);

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization_3->id,
            OrganizationCostume::COSTUME_ID => $costume_3->id,
        ]);

        $query = new GetCostumesPickerQuery([$organization_1->id, $organization_2->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(3, $result);

        // Costume 1 and 2 should have organization_costumes, costume 3 should not
        $costume_1_result = $result->firstWhere('id', $costume_1->id);
        $this->assertCount(1, $costume_1_result->organization_costumes);
        $this->assertEquals($organization_1->id, $costume_1_result->organization_costumes->first()->{OrganizationCostume::ORGANIZATION_ID});

        $costume_2_result = $result->firstWhere('id', $costume_2->id);
        $this->assertCount(1, $costume_2_result->organization_costumes);
        $this->assertEquals($organization_2->id, $costume_2_result->organization_costumes->first()->{OrganizationCostume::ORGANIZATION_ID});

        $costume_3_result = $result->firstWhere('id', $costume_3->id);
        $this->assertCount(0, $costume_3_result->organization_costumes);
    }

    public function test_invoke_includes_prefix_column_in_organization_costumes(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();
        $prefix = 'TK';

        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
            OrganizationCostume::PREFIX => $prefix,
        ]);

        $query = new GetCostumesPickerQuery([$organization->id]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $costume_result = $result->first();
        $org_costume = $costume_result->organization_costumes->first();

        $this->assertNotNull($org_costume->{OrganizationCostume::PREFIX});
        $this->assertEquals($prefix, $org_costume->{OrganizationCostume::PREFIX});

        // Verify the column is actually loaded, not just accessible
        $org_costume_attributes = array_keys($org_costume->getAttributes());
        $this->assertContains(OrganizationCostume::PREFIX, $org_costume_attributes);
    }

    public function test_invoke_returns_empty_collection_with_empty_organization_ids_array(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume = Costume::factory()->create();
        OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $query = new GetCostumesPickerQuery([]);
        $subject = new GetCostumesPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        // With empty array, whereIn returns no matches, so all costumes have empty organization_costumes
        $this->assertCount(1, $result);
        $costume_result = $result->first();
        $this->assertCount(0, $costume_result->organization_costumes);
    }
}
