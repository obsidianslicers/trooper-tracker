<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Troopers ApprovalListController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view all pending approvals
 * - Moderators can view only pending troopers they moderate
 * - Correct view is rendered
 * - Only pending troopers are shown
 */
class ApprovalListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_approvals_list_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approvals');
    }

    public function test_invoke_shows_only_pending_troopers(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->count(2)->create();
        Trooper::factory()->asActive()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 2;
        });
    }

    public function test_invoke_administrator_sees_all_pending_troopers(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asPending()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 3;
        });
    }

    public function test_invoke_moderator_sees_only_moderated_pending_troopers(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        Trooper::factory()->asPending()->withAssignment($moderated_org)->create();
        Trooper::factory()->asPending()->withAssignment($other_org)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->count() === 1;
        });
    }

    public function test_invoke_passes_troopers_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertViewHas('troopers');
    }

    public function test_invoke_allows_moderator_access(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_forbids_regular_trooper_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.troopers.approvals'));

        // Assert
        $response->assertForbidden();
    }
}
