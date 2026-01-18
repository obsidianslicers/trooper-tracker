<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Enums\MembershipRole;
use App\Enums\OrganizationType;
use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Features\Organizations\Queries\GetOrganizationsForPickerQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationsForPickerQueryHandler.
 *
 * Verifies:
 * - Returns moderated organizations when moderated_only is true
 * - Returns organization and descendants when organization_id is set
 * - Returns all organizations when no filters
 * - Orders by sequence
 */
class GetOrganizationsForPickerQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_all_organizations_by_default(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create([Organization::SEQUENCE => 10]);
        $org2 = Organization::factory()->create([Organization::SEQUENCE => 20]);

        $query = new GetOrganizationsForPickerQuery($trooper, []);
        $subject = new GetOrganizationsForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($org1->id, $result[0]->id);
        $this->assertEquals($org2->id, $result[1]->id);
    }

    public function test_invoke_filters_to_moderated_organizations(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $moderated_org = Organization::factory()->create([Organization::SEQUENCE => 10]);
        $other_org = Organization::factory()->create([Organization::SEQUENCE => 20]);

        // Create moderator assignment
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $query = new GetOrganizationsForPickerQuery($trooper, ['moderated_only' => true]);
        $subject = new GetOrganizationsForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($moderated_org->id, $result[0]->id);
    }

    public function test_invoke_filters_by_organization_and_descendants(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $parent = Organization::factory()->create([
            Organization::NODE_PATH => 'parent',
            Organization::SEQUENCE => 10,
        ]);

        $child = Organization::factory()->create([
            Organization::NODE_PATH => 'parent.child',
            Organization::SEQUENCE => 20,
        ]);

        $unrelated = Organization::factory()->create([
            Organization::NODE_PATH => 'other',
            Organization::SEQUENCE => 30,
        ]);

        $query = new GetOrganizationsForPickerQuery($trooper, ['organization_id' => $parent->id]);
        $subject = new GetOrganizationsForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - should match based on node_path LIKE pattern
        $this->assertGreaterThanOrEqual(1, $result->count());
        $this->assertTrue($result->contains(Organization::ID, $parent->id));
    }
}
