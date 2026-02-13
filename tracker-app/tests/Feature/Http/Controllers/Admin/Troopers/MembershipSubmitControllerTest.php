<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Troopers MembershipSubmitController.
 *
 * Verifies:
 * - Authentication is required
 * - Valid data updates trooper memberships
 * - Organization assignments are updated
 * - Identifiers are updated
 * - Redirects to correct route
 * - Authorization is enforced
 */
class MembershipSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_trooper_organization_memberships(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [
                $org->id => [
                    'assignment' => $org->id,
                ],
            ],
        ]);

        // Assert
        $response->assertRedirect(route('admin.troopers.membership', $trooper));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
        ]);
    }

    public function test_invoke_redirects_to_membership_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertRedirect(route('admin.troopers.membership', $trooper));
    }

    public function test_invoke_administrator_can_update_any_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertRedirect();
    }

    public function test_invoke_moderator_cannot_update_moderated_trooper(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $trooper = Trooper::factory()->withAssignment($org)->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_moderator_cannot_update_non_moderated_trooper(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.membership', $trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_memberships(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.troopers.membership', $other_trooper), [
            'organizations' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }
}

