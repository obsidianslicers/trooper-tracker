<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Awards ListTroopersController.
 *
 * Verifies:
 * - Authentication is required
 * - Administrators can view the award trooper list
 * - Assigned troopers are returned in descending award date order
 */
class ListTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->get(route('admin.awards.list-troopers', $award));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_view_trooper_list(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.awards.list-troopers', $award));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.list-troopers');
    }

    public function test_invoke_returns_troopers_in_descending_award_date_order(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $older_trooper = Trooper::factory()->asActive()->create();
        $newer_trooper = Trooper::factory()->asActive()->create();

        AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $older_trooper->id,
            AwardTrooper::AWARD_DATE => now()->subDays(5)->toDateString(),
        ]);
        AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $newer_trooper->id,
            AwardTrooper::AWARD_DATE => now()->subDays(1)->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.awards.list-troopers', $award));

        $response->assertViewHas('troopers');

        $troopers = $response->viewData('troopers');
        $this->assertSame($newer_trooper->id, $troopers->first()->id);
        $this->assertSame($older_trooper->id, $troopers->last()->id);
    }
}
