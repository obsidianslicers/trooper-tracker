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
 * Feature tests for Admin Awards ListController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view all awards
 * - Moderators can view only awards for moderated organizations
 * - Organization filtering works
 * - Correct view is rendered
 */
class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.awards.list'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_award_list_view(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($admin)->get(route('admin.awards.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.list');
    }

    public function test_invoke_administrator_sees_all_awards(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        Award::factory()->count(2)->create();

        $response = $this->actingAs($admin)->get(route('admin.awards.list'));

        $response->assertViewHas('awards', function ($awards)
        {
            return $awards->total() === 2;
        });
    }

    public function test_invoke_moderator_sees_only_moderated_awards(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        Award::factory()->create([
            Award::ORGANIZATION_ID => $moderated_org->id,
        ]);
        Award::factory()->create([
            Award::ORGANIZATION_ID => $other_org->id,
        ]);

        $response = $this->actingAs($moderator)->get(route('admin.awards.list'));

        $response->assertViewHas('awards', function ($awards)
        {
            return $awards->total() === 1;
        });
    }

    public function test_invoke_filters_by_organization(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Award::factory()->create([
            Award::ORGANIZATION_ID => $org1->id,
        ]);
        Award::factory()->create([
            Award::ORGANIZATION_ID => $org2->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.awards.list', [
            'organization_id' => $org1->id,
        ]));

        $response->assertViewHas('awards', function ($awards)
        {
            return $awards->total() === 1;
        });
        $response->assertViewHas('organization', function ($organization) use ($org1)
        {
            return $organization->id === $org1->id;
        });
    }
}
