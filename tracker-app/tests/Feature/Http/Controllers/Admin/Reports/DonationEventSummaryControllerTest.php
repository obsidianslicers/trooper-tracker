<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationEventSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_donation_event_summary_report(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.donation-event-summary'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.donation-event-summary');
    }

    public function test_invoke_streams_donation_event_summary_csv(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create(['name' => 'Florida Garrison']);
        Organization::factory()->create(['name' => 'Makaze Squad']);
        $event = Event::factory()
            ->asClosed()
            ->withOrganization($organization)
            ->withEventStart(Carbon::parse('2026-06-01 10:00:00'))
            ->create(['name' => 'Library Visit']);

        EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-06-01 10:00:00'))
            ->withShiftEndsAt(Carbon::parse('2026-06-01 12:00:00'))
            ->withCharityData(
                charity_name: 'Local Library',
                direct_funds: 100,
                indirect_funds: 25,
                charity_notes: 'Summer reading kickoff',
            )
            ->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.donation-event-summary', [
            'format' => 'csv',
            'date_start' => '2026-06-01',
            'date_end' => '2026-06-01',
            'organization_ids' => [$organization->id],
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('"Library Visit",2026-06-01,"Florida Garrison",10am-12pm,"Local Library",100,25,125,2,0,0,"Summer reading kickoff"', $content);
        $this->assertStringContainsString('Total,,,,,100,25,125,2,0,0,', $content);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.donation-event-summary'));

        $response->assertRedirect(route('auth.login'));
    }
}
