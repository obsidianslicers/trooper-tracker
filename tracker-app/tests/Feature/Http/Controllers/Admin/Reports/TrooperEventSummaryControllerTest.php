<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperEventSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.trooper-event-summary'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_trooper_event_summary_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.trooper-event-summary'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.trooper-event-summary');
    }

    public function test_invoke_passes_trooper_events_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.trooper-event-summary'));

        $response->assertOk();
        $response->assertViewHas('trooper_events');
    }

    public function test_invoke_passes_lookback_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.trooper-event-summary'));

        $response->assertOk();
        $response->assertViewHas('lookback');

        $lookback = $response->viewData('lookback');
        $this->assertEquals(30, $lookback);
    }
}
