<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TroopersWithoutActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.troopers-without-activity'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_troopers_without_activity_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.troopers-without-activity');
    }

    public function test_invoke_passes_troopers_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $response->assertViewHas('troopers');
    }

    public function test_invoke_passes_lookback_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $response->assertViewHas('lookback');
    }

    public function test_invoke_only_includes_active_troopers(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        // Create troopers with different statuses
        Trooper::factory()->asActive()->count(2)->create();
        Trooper::factory()->asPending()->count(2)->create();
        Trooper::factory()->asRetired()->count(2)->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.troopers-without-activity'));

        $response->assertOk();
        $troopers = $response->viewData('troopers');

        // Should only include active troopers
        foreach ($troopers as $trooper)
        {
            $this->assertEquals(MembershipStatus::ACTIVE, $trooper->membership_status);
        }
    }
}
