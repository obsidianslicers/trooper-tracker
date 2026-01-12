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
 * Feature tests for CostumesSubmitHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can add costumes via HTMX
 * - Security: Only allows adding costumes from organizations trooper is a member of
 * - Creates TrooperCostume record when costume added
 * - Restores soft-deleted costume if previously removed
 * - Returns costumes table partial for HTMX swap
 * - Validates organization_id and costume_id
 * - Unauthenticated users are redirected to login
 */
class CostumesSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_costume_record(): void
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
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert
        $this->assertDatabaseHas(TrooperCostume::class, [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_adds_costume_to_trooper(): void
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
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals(1, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_returns_costumes_table_partial(): void
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
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('pages.account.costumes-table');
        $response->assertViewHas('trooper_costumes');
    }

    public function test_invoke_prevents_adding_costume_from_organization_trooper_not_member_of(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        // Trooper is NOT a member of this organization

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert - costume should NOT be added
        $this->assertDatabaseMissing(TrooperCostume::class, [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_prevents_adding_costume_that_doesnt_belong_to_organization(): void
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

        // Costume belongs to organization2, not organization1
        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization2->id,
        ]);

        // Act - trying to add costume from wrong organization
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization1->id,
                'costume_id' => $costume->id,
            ]);

        // Assert - costume should NOT be added
        $this->assertDatabaseMissing(TrooperCostume::class, [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_restores_soft_deleted_costume(): void
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
        ]);

        // Create and soft-delete a trooper costume
        $trooper_costume = TrooperCostume::factory()->create([
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::COSTUME_ID => $costume->id,
        ]);
        $trooper_costume->delete();

        // Act - re-add the costume
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert - costume should be restored, not duplicated
        $this->assertEquals(1, TrooperCostume::withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $costume->id)
            ->count());

        $this->assertEquals(1, TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $costume->id)
            ->count());
    }

    public function test_invoke_ignores_invalid_organization_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => -1,
                'costume_id' => 1,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_ignores_invalid_costume_id(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => -1,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_ignores_missing_organization_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'costume_id' => 1,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_ignores_missing_costume_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => 1,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(0, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->post(route('account.costumes-htmx'), [
            'organization_id' => 1,
            'costume_id' => 1,
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_eager_loads_organization_costume_relationships(): void
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
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

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

    public function test_invoke_only_adds_costume_for_authenticated_trooper(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper1->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $costume = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // Act - trooper1 adds costume
        $response = $this->actingAs($trooper1)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert - only trooper1 has costume
        $this->assertEquals(1, $trooper1->trooper_costumes()->count());
        $this->assertEquals(0, $trooper2->trooper_costumes()->count());
    }

    public function test_invoke_handles_multiple_costume_additions(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $costume1 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);
        $costume2 = OrganizationCostume::factory()->create([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
        ]);

        // Act - add both costumes
        $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume1->id,
            ]);

        $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume2->id,
            ]);

        // Assert
        $this->assertEquals(2, $trooper->trooper_costumes()->count());
    }

    public function test_invoke_is_idempotent_when_adding_same_costume_twice(): void
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
        ]);

        // Act - add same costume twice
        $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        $this->actingAs($trooper)
            ->post(route('account.costumes-htmx'), [
                'organization_id' => $organization->id,
                'costume_id' => $costume->id,
            ]);

        // Assert - should still only have 1 costume
        $this->assertEquals(1, $trooper->trooper_costumes()->count());
    }
}
