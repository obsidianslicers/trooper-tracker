<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Http\Controllers\Account\SetupController;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SetupController.
 *
 * Verifies:
 * - Authenticated trooper can access setup page.
 * - Displays organization memberships for the trooper.
 * - Passes trooper and organization data to the view.
 * - Requires authentication.
 * - Uses GetTrooperOrganizationMembershipsQuery service.
 */
class SetupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_setup_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.setup');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NAME => 'Test Trooper',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertViewHas('trooper', function ($viewTrooper) use ($trooper)
        {
            return $viewTrooper->id === $trooper->id
                && $viewTrooper->name === 'Test Trooper';
        });
    }

    public function test_invoke_passes_organization_memberships_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $org1 = Organization::factory()->create([
            Organization::NAME => 'Organization Alpha',
        ]);

        $org2 = Organization::factory()->create([
            Organization::NAME => 'Organization Beta',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertViewHas('organization_memberships');

        $organizations = $response->viewData('organization_memberships');
        $this->assertCount(2, $organizations);

        // Organizations should be ordered by name
        $this->assertEquals('Organization Alpha', $organizations[0]->name);
        $this->assertEquals('Organization Beta', $organizations[1]->name);
    }

    public function test_invoke_includes_trooper_assignments_in_organization_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([
            Organization::NAME => 'Test Club',
            Organization::NODE_PATH => '/1/',
        ]);

        $region = Organization::factory()->create([
            Organization::PARENT_ID => $club->id,
            Organization::NAME => 'Test Region',
            Organization::NODE_PATH => '/1/2/',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        // Create assignment for trooper to the region
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertViewHas('organization_memberships');

        $organizations = $response->viewData('organization_memberships');
        $testClub = $organizations->firstWhere('name', 'Test Club');

        $this->assertNotNull($testClub);
        $this->assertTrue(isset($testClub->assignment));
        $this->assertEquals('Test Region', $testClub->assignment->name);
    }

    public function test_invoke_handles_trooper_with_no_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        Organization::factory()->create([
            Organization::NAME => 'Organization One',
        ]);

        Organization::factory()->create([
            Organization::NAME => 'Organization Two',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization_memberships');

        $organizations = $response->viewData('organization_memberships');
        $this->assertCount(2, $organizations);

        // Neither should have assignments
        foreach ($organizations as $org)
        {
            $this->assertFalse(isset($org->assignment));
        }
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(action(SetupController::class));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_uses_get_trooper_organization_memberships_query(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()->create([
            Organization::NAME => 'Query Test Club',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization_memberships');

        // The query should return all top-level organizations
        $organizations = $response->viewData('organization_memberships');
        $this->assertTrue($organizations->contains('name', 'Query Test Club'));
    }

    public function test_invoke_handles_multiple_organization_types(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $club1 = Organization::factory()->create([
            Organization::NAME => 'Club One',
            Organization::NODE_PATH => '/1/',
        ]);

        $region1 = Organization::factory()->create([
            Organization::PARENT_ID => $club1->id,
            Organization::NAME => 'Region One',
            Organization::NODE_PATH => '/1/2/',
            Organization::TYPE => \App\Enums\OrganizationType::REGION,
        ]);

        $club2 = Organization::factory()->create([
            Organization::NAME => 'Club Two',
            Organization::NODE_PATH => '/3/',
        ]);

        // Assign trooper to region1
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $region1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(action(SetupController::class));

        // Assert
        $response->assertOk();

        $organizations = $response->viewData('organization_memberships');

        // Should only return top-level organizations (clubs), not regions
        $this->assertEquals(2, $organizations->count());

        $clubOne = $organizations->firstWhere('name', 'Club One');
        $clubTwo = $organizations->firstWhere('name', 'Club Two');

        $this->assertNotNull($clubOne);
        $this->assertNotNull($clubTwo);

        // Club One should have assignment
        $this->assertTrue(isset($clubOne->assignment));
        $this->assertEquals('Region One', $clubOne->assignment->name);

        // Club Two should not have assignment
        $this->assertFalse(isset($clubTwo->assignment));
    }
}
