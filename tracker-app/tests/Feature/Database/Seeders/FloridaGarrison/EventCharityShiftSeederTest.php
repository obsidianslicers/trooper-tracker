<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\FloridaGarrison;

use App\Models\Event;
use App\Models\EventShift;
use Database\Seeders\FloridaGarrison\EventCharityShiftSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventCharityShiftSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_updates_shift_charity_and_prints_summary_breakdown(): void
    {
        $this->createLegacyEventsTable();
        $this->createTemporaryEventCharityColumns();

        $mapped_event = Event::factory()->create();
        EventShift::factory()->forEvent($mapped_event)->create([EventShift::ID => 100]);
        DB::table('events')->insert([
            'id' => 100,
            'charityDirectFunds' => 25,
            'charityIndirectFunds' => 5,
            'charityName' => 'Kids &amp; Co',
            'charityAddHours' => 2,
            'charityNote' => 'Bring extra smiles &amp; stickers',
        ]);

        $empty_mapped_event = Event::factory()->create();
        EventShift::factory()->forEvent($empty_mapped_event)->create([EventShift::ID => 101]);
        DB::table('events')->insert([
            'id' => 101,
            'charityDirectFunds' => 0,
            'charityIndirectFunds' => 0,
            'charityName' => null,
            'charityAddHours' => null,
            'charityNote' => null,
        ]);

        $no_legacy_event = Event::factory()->create();
        EventShift::factory()->forEvent($no_legacy_event)->create([EventShift::ID => 102]);

        $fallback_event = Event::factory()->create();
        DB::table('tt_events')
            ->where('id', $fallback_event->id)
            ->update([
                'charity_direct_funds' => 40,
                'charity_indirect_funds' => 10,
                'charity_name' => 'Event &amp; Fallback',
                'charity_hours' => 3,
                'charity_notes' => 'Copied from event &amp; decoded',
            ]);
        EventShift::factory()
            ->forEvent($fallback_event)
            ->withShiftStartsAt(now()->setTime(9, 0))
            ->create([EventShift::ID => 103]);

        $skip_event = Event::factory()->create();
        DB::table('tt_events')
            ->where('id', $skip_event->id)
            ->update([
                'charity_direct_funds' => 99,
                'charity_indirect_funds' => 0,
                'charity_name' => 'Should Not Copy',
            ]);
        EventShift::factory()
            ->forEvent($skip_event)
            ->withShiftStartsAt(now()->setTime(9, 0))
            ->create([EventShift::ID => 104]);
        EventShift::factory()
            ->forEvent($skip_event)
            ->withShiftStartsAt(now()->setTime(13, 0))
            ->create([
                EventShift::ID => 105,
                EventShift::CHARITY_NOTES => 'Existing shift note blocks fallback',
            ]);

        $no_shift_event = Event::factory()->create();
        DB::table('tt_events')
            ->where('id', $no_shift_event->id)
            ->update([
                'charity_direct_funds' => 12,
                'charity_indirect_funds' => 0,
                'charity_name' => 'No Shift Charity',
            ]);

        $this->artisan('db:seed', ['--class' => EventCharityShiftSeeder::class])
            ->expectsOutput('Event charity shift backfill')
            ->expectsOutput('Pass 1: legacy shift charity mapping')
            ->expectsOutput('- Shifts scanned: 6')
            ->expectsOutput('- Legacy rows matched by shift id: 2')
            ->expectsOutput('- Shifts updated: 2')
            ->expectsOutput('- Updated with charity data: 1')
            ->expectsOutput('- Updated with empty charity data: 1')
            ->expectsOutput('- Skipped, no legacy row: 4')
            ->expectsOutput('Pass 2: event-level fallback')
            ->expectsOutput('- Events with event-level charity scanned: 3')
            ->expectsOutput('- Fallback updates applied: 1')
            ->expectsOutput('- Skipped, shift charity already exists: 1')
            ->expectsOutput('- Skipped, no active shifts: 1')
            ->expectsOutput('- Skipped, event charity columns unavailable: 0')
            ->expectsOutput('Done.')
            ->assertSuccessful();

        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 100,
            'charity_direct_funds' => 25,
            'charity_indirect_funds' => 5,
            'charity_name' => 'Kids & Co',
            'charity_hours' => 2,
            'charity_notes' => 'Bring extra smiles & stickers',
        ]);
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 101,
            'charity_direct_funds' => 0,
            'charity_indirect_funds' => 0,
            'charity_name' => null,
            'charity_hours' => null,
            'charity_notes' => null,
        ]);
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 103,
            'charity_direct_funds' => 40,
            'charity_indirect_funds' => 10,
            'charity_name' => 'Event & Fallback',
            'charity_hours' => 3,
            'charity_notes' => 'Copied from event & decoded',
        ]);
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 104,
            'charity_direct_funds' => 0,
            'charity_indirect_funds' => 0,
            'charity_name' => null,
        ]);
    }

    public function test_run_after_event_charity_columns_are_removed_skips_event_level_fallback(): void
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create([EventShift::ID => 200]);

        $this->assertFalse(Schema::hasTable('events'));
        $this->assertFalse(Schema::hasColumn('tt_events', 'charity_direct_funds'));

        $this->artisan('db:seed', ['--class' => EventCharityShiftSeeder::class])
            ->expectsOutput('Event charity shift backfill')
            ->expectsOutput('Pass 1: legacy shift charity mapping')
            ->expectsOutput('- Shifts scanned: 1')
            ->expectsOutput('- Legacy rows matched by shift id: 0')
            ->expectsOutput('- Shifts updated: 0')
            ->expectsOutput('- Updated with charity data: 0')
            ->expectsOutput('- Updated with empty charity data: 0')
            ->expectsOutput('- Skipped, no legacy row: 1')
            ->expectsOutput('Pass 2: event-level fallback')
            ->expectsOutput('- Events with event-level charity scanned: 0')
            ->expectsOutput('- Fallback updates applied: 0')
            ->expectsOutput('- Skipped, shift charity already exists: 0')
            ->expectsOutput('- Skipped, no active shifts: 0')
            ->expectsOutput('- Skipped, event charity columns unavailable: 1')
            ->expectsOutput('Done.')
            ->assertSuccessful();

        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 200,
            'charity_direct_funds' => 0,
            'charity_indirect_funds' => 0,
            'charity_name' => null,
            'charity_hours' => null,
            'charity_notes' => null,
        ]);
    }

    private function createLegacyEventsTable(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->integer('charityDirectFunds')->nullable();
            $table->integer('charityIndirectFunds')->nullable();
            $table->string('charityName')->nullable();
            $table->integer('charityAddHours')->nullable();
            $table->text('charityNote')->nullable();
        });
    }

    private function createTemporaryEventCharityColumns(): void
    {
        if (Schema::hasColumn('tt_events', 'charity_direct_funds'))
        {
            return;
        }

        Schema::table('tt_events', function (Blueprint $table): void {
            $table->integer('charity_direct_funds')->default(0);
            $table->integer('charity_indirect_funds')->default(0);
            $table->string('charity_name', 128)->nullable();
            $table->integer('charity_hours')->nullable();
            $table->text('charity_notes')->nullable();
        });
    }
}
