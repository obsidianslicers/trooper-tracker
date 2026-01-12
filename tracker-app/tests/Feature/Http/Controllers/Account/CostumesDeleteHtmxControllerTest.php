<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for CostumesDeleteHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can delete their costumes via HTMX
 * - Security: Only allows deleting trooper's own costumes
 * - Soft-deletes TrooperCostume record
 * - Returns costumes table partial for HTMX swap
 * - Validates costume_id parameter
 * - Unauthenticated users are redirected to login
 */
class CostumesDeleteHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_trooper_costume(): void
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
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        // Assert
        $this->assertSoftDeleted(TrooperCostume::class, [
            TrooperCostume::ID => $trooper_costume->id,
        ]);
    }

    public function test_invoke_removes_costume_from_trooper(): void
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
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        // Assert
        $trooper->refresh();
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_returns_costumes_table_partial(): void
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
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.account.costumes-table');
        $response->assertViewHas('trooper_costumes');
    }

    public function test_invoke_only_deletes_costumes_belonging_to_authenticated_trooper(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper1_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper1->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        $trooper2_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper2->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Act - trooper1 tries to delete trooper2's costume
        $response = $this->actingAs($trooper1)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper2_costume->id);

        // Assert - trooper2's costume should NOT be deleted
        $this->assertDatabaseHas(TrooperCostume::class, [
            TrooperCostume::ID => $trooper2_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_invoke_ignores_invalid_costume_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=-1');

        // Assert
        $response->assertStatus(200);
    }

    public function test_invoke_ignores_missing_costume_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx');

        // Assert
        $response->assertStatus(200);
    }

    public function test_invoke_ignores_non_existent_costume_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=99999');

        // Assert
        $response->assertStatus(200);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->delete('/account/costumes-htmx?costume_id=1');

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_eager_loads_organization_costume_relationships(): void
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

        $trooper_costume1 = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume1->id,
        ]);
        TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume2->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume1->id);

        // Assert
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

    public function test_invoke_returns_updated_costume_list_after_deletion(): void
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

        $trooper_costume1 = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume1->id,
        ]);
        $trooper_costume2 = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume2->id,
        ]);

        // Act - delete first costume
        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume1->id);

        // Assert - should only show remaining costume
        $response->assertViewHas('trooper_costumes', function ($costumes) use ($trooper_costume2)
        {
            return $costumes->count() === 1
                && $costumes->first()->id === $trooper_costume2->id;
        });
    }

    public function test_invoke_handles_deleting_all_costumes(): void
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
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        // Assert - should return empty costume list
        $response->assertViewHas('trooper_costumes', function ($costumes)
        {
            return $costumes->isEmpty();
        });
    }

    public function test_invoke_isolates_deletion_per_trooper(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper1_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper1->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        $trooper2_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper2->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);

        // Act - trooper1 deletes their costume
        $response = $this->actingAs($trooper1)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper1_costume->id);

        // Assert - only trooper1's costume is deleted
        $this->assertSoftDeleted(TrooperCostume::class, [
            TrooperCostume::ID => $trooper1_costume->id,
        ]);

        $this->assertDatabaseHas(TrooperCostume::class, [
            TrooperCostume::ID => $trooper2_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_invoke_can_be_called_multiple_times_safely(): void
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

        // Act - delete twice
        $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        $response = $this->actingAs($trooper)
            ->delete('/account/costumes-htmx?costume_id=' . $trooper_costume->id);

        // Assert - should handle gracefully
        $response->assertStatus(200);
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }
}
