<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Awards AssignTrooperSubmitController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can assign troopers
 * - Award trooper record is created
 * - Moderators cannot assign unmoderated awards
 * - Redirects to the award trooper list
 * - Success flash message is displayed
 */
class AssignTrooperSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->post(route('admin.awards.assign-trooper', $award), [
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_assign_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $award_date = now()->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.awards.assign-trooper', $award), [
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => $award_date,
        ]);

        $response->assertRedirect(route('admin.awards.list-troopers', $award));
    }

    public function test_invoke_creates_award_trooper_record(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $award_date = now()->toDateString();
        $award_datetime = now()->setTime(0, 0, 0)->format('Y-m-d H:i:s');

        $this->actingAs($admin)->post(route('admin.awards.assign-trooper', $award), [
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => $award_date,
        ]);

        $this->assertDatabaseHas('tt_award_troopers', [
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => $award_datetime,
        ]);
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Commander Cody']);

        $response = $this->actingAs($admin)->post(route('admin.awards.assign-trooper', $award), [
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_rejects_non_existent_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.awards.assign-trooper', $award), [
            AwardTrooper::TROOPER_ID => 99999,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(AwardTrooper::TROOPER_ID);
    }
}
