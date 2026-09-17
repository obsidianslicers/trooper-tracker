<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCorruptedEventTrooperCreditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_no_rows_found(): void
    {
        $this->artisan('tracker:report-corrupted-event-trooper-credit')
            ->assertExitCode(0)
            ->expectsOutputToContain('No corrupted event_trooper credit rows found.');
    }

    public function test_reports_corrupted_row(): void
    {
        // Only assert on the trooper name and the summary count here — the console
        // table's column widths depend on terminal width, which Symfony's Table caches
        // per-process and can wrap/hard-break long cell values unpredictably under the
        // test harness. Exact per-column content (including the event) is covered by
        // GetCorruptedEventTrooperCreditQueryHandlerTest instead.
        $trooper = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'CorruptedTrooper']);
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        $this->artisan('tracker:report-corrupted-event-trooper-credit')
            ->assertExitCode(0)
            ->expectsOutputToContain('CorruptedTrooper')
            ->expectsOutputToContain('Found 1 corrupted row(s)');
    }

    public function test_filters_by_trooper_option(): void
    {
        $trooper = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Target Trooper']);
        $other_trooper = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Other Trooper']);
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        $this->artisan('tracker:report-corrupted-event-trooper-credit', ['--trooper' => $trooper->id])
            ->assertExitCode(0)
            ->expectsOutputToContain('Target Trooper')
            ->expectsOutputToContain('Found 1 corrupted row(s)');
    }
}
