<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusChangeLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.status-change-log'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_status_change_log_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.status-change-log'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.status-change-log');
    }

    public function test_invoke_passes_changes_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.status-change-log'));

        $response->assertOk();
        $response->assertViewHas('changes');
    }

    public function test_invoke_passes_lookback_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.status-change-log'));

        $response->assertOk();
        $response->assertViewHas('lookback');

        $lookback = $response->viewData('lookback');
        $this->assertEquals(30, $lookback);
    }
}
