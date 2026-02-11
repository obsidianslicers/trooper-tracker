<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Awards UpdateController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view the update form
 * - Moderators can view the update form for moderated awards
 * - Moderators cannot view unmoderated awards
 */
class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.update', $award));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_update_form(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.awards.update', $award));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.update');
    }

    public function test_invoke_moderator_can_view_update_form_for_moderated_award(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $award = Award::factory()->create([
            Award::ORGANIZATION_ID => $organization->id,
        ]);

        $response = $this->actingAs($moderator)
            ->get(route('admin.awards.update', $award));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.update');
    }

    public function test_invoke_moderator_cannot_view_unmoderated_award(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($moderator)
            ->get(route('admin.awards.update', $award));

        $response->assertForbidden();
    }
}
