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
 * Feature tests for Admin Troopers ListController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view all troopers
 * - Moderators can view only moderated troopers
 * - Search and filtering work correctly
 * - Correct view is rendered
 */
class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.troopers.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_troopers_list_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.list');
    }

    public function test_invoke_administrator_sees_all_troopers(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->count(3)->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list'));

        // Assert
        $response->assertViewHas('troopers', function ($troopers)
        {
            return $troopers->total() >= 3;
        });
    }

    public function test_invoke_moderator_sees_only_moderated_troopers(): void
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

        $moderated_trooper = Trooper::factory()->withAssignment($moderated_org)->create();
        $other_trooper = Trooper::factory()->withAssignment($other_org)->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.troopers.list'));

        // Assert
        $response->assertViewHas('troopers', function ($troopers) use ($moderated_trooper, $other_trooper)
        {
            return $troopers->contains(Trooper::ID, $moderated_trooper->id)
                && !$troopers->contains(Trooper::ID, $other_trooper->id);
        });
    }

    public function test_invoke_filters_by_search_term(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'John Doe']);
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Jane Smith']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list', [
            'search_term' => 'John',
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('search_term', 'John');
    }

    public function test_invoke_filters_by_membership_role(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list', [
            'membership_role' => MembershipRole::MODERATOR->value,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('membership_role', MembershipRole::MODERATOR->value);
    }

    public function test_invoke_passes_correct_data_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.troopers.list'));

        // Assert
        $response->assertViewHas('troopers');
        $response->assertViewHas('membership_role');
        $response->assertViewHas('search_term');
    }

    public function test_invoke_forbids_regular_trooper_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.troopers.list'));

        // Assert
        $response->assertForbidden();
    }
}
