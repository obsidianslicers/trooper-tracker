<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CostumeEventSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_costume_event_summary_report(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.costume-event-summary'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/CostumeEventSummary')
            ->has('costumes.data')
            ->has('organizations')
            ->has('filters')
            ->has('sorts')
            ->has('csv_url')
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.costume-event-summary'));

        $response->assertRedirect(route('auth.login'));
    }
}
