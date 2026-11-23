<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowCostumeEventSummaryControllerTest extends TestCase
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

    public function test_invoke_streams_costume_event_summary_csv(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.costume-event-summary', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDownload('costume-event-summary-'.now()->format('Y-m-d').'.csv');
        $this->assertStringContainsString('Costume,"Unique Events","Total Uses"', $response->streamedContent());
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.costume-event-summary'));

        $response->assertRedirect(route('auth.login'));
    }
}
