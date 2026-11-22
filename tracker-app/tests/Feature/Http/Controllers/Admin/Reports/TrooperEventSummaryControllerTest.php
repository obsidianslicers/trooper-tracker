<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrooperEventSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_trooper_event_summary_report(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.trooper-event-summary'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/TrooperEventSummary')
            ->has('troopers.data')
            ->has('organizations')
            ->has('filters')
            ->has('sorts')
            ->has('csv_url')
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.trooper-event-summary'));

        $response->assertRedirect(route('auth.login'));
    }
}
