<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Troopers AuthoritySubmitController.
 *
 * Verifies:
 * - Authentication is required
 * - Only administrators can update authority
 * - Valid data updates trooper authority
 * - Organization moderator assignments are updated
 * - Flash message is set
 * - Redirects to correct route
 */
class AuthoritySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->post(route('admin.troopers.authority', $trooper), [
            'membership_role' => MembershipRole::MEMBER->value,
            'organizations' => [],
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_trooper_membership_role(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.authority', $trooper), [
            'membership_role' => MembershipRole::MODERATOR->value,
            'organizations' => [],
        ]);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR->value,
        ]);
    }

    public function test_invoke_updates_organization_moderator_assignments(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.authority', $trooper), [
            'membership_role' => MembershipRole::MODERATOR->value,
            'organizations' => [
                $org->id => ['is_moderator' => true],
            ],
        ]);

        // Assert
        $response->assertRedirect(route('admin.troopers.list'));
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }

    public function test_invoke_sets_flash_message(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.troopers.authority', $trooper), [
            'membership_role' => MembershipRole::MEMBER->value,
            'organizations' => [],
        ]);

        // Assert
        $response->assertSessionHas('flash_messages');
    }

    public function test_invoke_forbids_moderator_access(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.troopers.authority', $trooper), [
            'membership_role' => MembershipRole::MEMBER->value,
            'organizations' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_forbids_regular_trooper_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.troopers.authority', $other_trooper), [
            'membership_role' => MembershipRole::MEMBER->value,
            'organizations' => [],
        ]);

        // Assert
        $response->assertForbidden();
    }
}

