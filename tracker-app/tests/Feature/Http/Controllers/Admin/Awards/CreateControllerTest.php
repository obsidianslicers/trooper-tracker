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
 * Feature tests for Admin Awards CreateController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators and moderators can view the create form
 * - Regular troopers are forbidden
 * - Organization pre-selection works when provided
 * - Moderators cannot pre-select unmoderated organizations
 */
class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.awards.create'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_create_form(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.awards.create'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.create');
    }

    public function test_invoke_moderator_can_view_create_form(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)
            ->get(route('admin.awards.create'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.create');
    }

    public function test_invoke_regular_trooper_cannot_view_create_form(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)
            ->get(route('admin.awards.create'));

        $response->assertForbidden();
    }

    public function test_invoke_assigns_organization_from_query_parameter(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.awards.create', ['organization_id' => $organization->id]));

        $response->assertViewHas('award', function ($award) use ($organization)
        {
            return $award->organization_id === $organization->id
                && $award->organization->id === $organization->id;
        });
    }

    public function test_invoke_moderator_cannot_assign_unmoderated_organization(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => Organization::factory()->create()->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->actingAs($moderator)
            ->get(route('admin.awards.create', ['organization_id' => $organization->id]))
            ->assertNotFound();
    }
}
