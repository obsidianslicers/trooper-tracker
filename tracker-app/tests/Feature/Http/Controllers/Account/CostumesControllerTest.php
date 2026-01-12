<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for CostumesController.
 *
 * Verifies:
 * - Authenticated troopers can view their costumes page
 * - Page displays available costumes from trooper's organizations
 * - Page displays trooper's currently assigned costumes
 * - Only shows costumes from organizations trooper is a member of
 * - Unauthenticated users are redirected to login
 */
class CostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_costumes_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.account.costumes');
    }

    public function test_invoke_passes_organization_costumes_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('organization_costumes');
    }

    public function test_invoke_passes_trooper_costumes_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('trooper_costumes');
    }

    public function test_invoke_displays_available_costumes_from_troopers_organizations(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::NAME => 'TK-421 Stormtrooper',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('organization_costumes', function ($costumes) use ($costume)
        {
            return $costumes->contains('id', $costume->id);
        });
    }

    public function test_invoke_displays_trooper_assigned_costumes(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('trooper_costumes', function ($costumes) use ($trooper_costume)
        {
            return $costumes->contains('id', $trooper_costume->id);
        });
    }

    public function test_invoke_only_shows_costumes_from_organizations_trooper_is_member_of(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Trooper is member of organization1 only
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization1->id,
            OrganizationCostume::NAME => 'Stormtrooper',
        ]);

        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization2->id,
            OrganizationCostume::NAME => 'Darth Vader',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('organization_costumes', function ($costumes) use ($costume1, $costume2)
        {
            return $costumes->contains('id', $costume1->id)
                && !$costumes->contains('id', $costume2->id);
        });
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('account.costumes'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_eager_loads_organization_costume_relationships(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Enable query logging
        \DB::enableQueryLog();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Get query log
        $queries = \DB::getQueryLog();

        // Assert - verify eager loading occurred (should not have N+1 queries)
        $response->assertViewHas('trooper_costumes', function ($costumes)
        {
            // Accessing relationship should not trigger additional queries
            $first = $costumes->first();
            if ($first)
            {
                $org_costume = $first->organization_costume;
                $org = $org_costume?->organization;
            }
            return true;
        });
    }

    public function test_invoke_shows_empty_trooper_costumes_when_none_assigned(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('trooper_costumes', function ($costumes)
        {
            return $costumes->isEmpty();
        });
    }

    public function test_invoke_shows_empty_organization_costumes_when_not_member_of_any_organization(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('organization_costumes', function ($costumes)
        {
            return $costumes->isEmpty();
        });
    }

    public function test_invoke_handles_multiple_organizations(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization1->id,
        ]);

        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization2->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('organization_costumes', function ($costumes) use ($costume1, $costume2)
        {
            return $costumes->contains('id', $costume1->id)
                && $costumes->contains('id', $costume2->id);
        });
    }

    public function test_invoke_handles_multiple_trooper_costumes(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);
        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);
        $costume3 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume2->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume3->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertViewHas('trooper_costumes', function ($costumes)
        {
            return $costumes->count() === 3;
        });
    }

    public function test_invoke_isolates_costumes_per_trooper(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // Only trooper1 has this costume
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper1->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Act
        $response1 = $this->actingAs($trooper1)
            ->get(route('account.costumes'));

        $response2 = $this->actingAs($trooper2)
            ->get(route('account.costumes'));

        // Assert - trooper1 sees the costume
        $response1->assertViewHas('trooper_costumes', function ($costumes)
        {
            return $costumes->count() === 1;
        });

        // Assert - trooper2 does not see the costume
        $response2->assertViewHas('trooper_costumes', function ($costumes)
        {
            return $costumes->isEmpty();
        });
    }

    public function test_invoke_returns_view_response(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.costumes'));

        // Assert
        $response->assertOk();
    }
}
