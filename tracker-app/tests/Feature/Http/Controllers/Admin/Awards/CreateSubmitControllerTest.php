<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Awards CreateSubmitController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can create awards
 * - Moderators can create awards for moderated organizations
 * - Regular troopers are forbidden
 * - Redirects to the award update page
 */
class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->post(route('admin.awards.create'), [
            Award::ORGANIZATION_ID => $organization->id,
            Award::NAME => 'Meritorious Service',
            Award::FREQUENCY => AwardFrequency::ONCE->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_create_award(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.awards.create'), [
            Award::ORGANIZATION_ID => $organization->id,
            Award::NAME => 'Meritorious Service',
            Award::FREQUENCY => AwardFrequency::ONCE->value,
        ]);

        $award = Award::where(Award::NAME, 'Meritorious Service')->first();
        $response->assertRedirect(route('admin.awards.update', $award));
        $this->assertDatabaseHas('tt_awards', [
            Award::NAME => 'Meritorious Service',
            Award::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_moderator_can_create_award_for_moderated_organization(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $response = $this->actingAs($moderator)->post(route('admin.awards.create'), [
            Award::ORGANIZATION_ID => $organization->id,
            Award::NAME => 'Unit Citation',
            Award::FREQUENCY => AwardFrequency::ANNUALLY->value,
        ]);

        $award = Award::where(Award::NAME, 'Unit Citation')->first();
        $response->assertRedirect(route('admin.awards.update', $award));
        $this->assertDatabaseHas('tt_awards', [
            Award::NAME => 'Unit Citation',
            Award::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_regular_trooper_cannot_create_award(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post(route('admin.awards.create'), [
            Award::ORGANIZATION_ID => $organization->id,
            Award::NAME => 'Service Ribbon',
            Award::FREQUENCY => AwardFrequency::ONCE->value,
        ]);

        $response->assertForbidden();
    }
}
