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
 * Feature tests for Admin Awards UpdateSubmitController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can update awards
 * - Moderators can update moderated awards
 * - Moderators cannot update unmoderated awards
 * - Redirects to the awards list
 */
class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->post(route('admin.awards.update', $award), [
            Award::NAME => 'Updated Name',
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_update_award_name(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create([
            Award::NAME => 'Original Name',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.awards.update', $award), [
            Award::NAME => 'Updated Name',
        ]);

        $response->assertRedirect(route('admin.awards.list'));
        $this->assertDatabaseHas('tt_awards', [
            Award::ID => $award->id,
            Award::NAME => 'Updated Name',
        ]);
    }

    public function test_invoke_moderator_can_update_moderated_award(): void
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
            Award::NAME => 'Old Name',
        ]);

        $response = $this->actingAs($moderator)->post(route('admin.awards.update', $award), [
            Award::NAME => 'New Name',
        ]);

        $response->assertRedirect(route('admin.awards.list'));
        $this->assertDatabaseHas('tt_awards', [
            Award::ID => $award->id,
            Award::NAME => 'New Name',
        ]);
    }

    public function test_invoke_moderator_cannot_update_unmoderated_award(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($moderator)->post(route('admin.awards.update', $award), [
            Award::NAME => 'New Name',
        ]);

        $response->assertForbidden();
    }
}
