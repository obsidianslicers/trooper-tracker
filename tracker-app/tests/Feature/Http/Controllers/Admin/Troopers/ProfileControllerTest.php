<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Troopers ProfileController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view any trooper profile
 * - Moderators can view profiles of troopers they moderate
 * - Regular troopers cannot view admin profiles
 * - Correct view is rendered
 * - Authorization is enforced
 */
class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_trooper_profile_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.profile');
    }

    public function test_invoke_passes_trooper_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create([
            Trooper::DISPLAY_NAME => 'Test Trooper',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertViewHas('trooper', function ($view_trooper) use ($trooper)
        {
            return $view_trooper->id === $trooper->id
                && $view_trooper->display_name === 'Test Trooper';
        });
    }

    public function test_invoke_administrator_can_view_any_trooper(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_can_view_moderated_trooper(): void
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
        $response = $this->actingAs($moderator)->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_cannot_view_non_moderated_trooper(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.profile', $trooper));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_view_other_profiles(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.troopers.profile', $other_trooper));

        // Assert
        $response->assertForbidden();
    }
}
