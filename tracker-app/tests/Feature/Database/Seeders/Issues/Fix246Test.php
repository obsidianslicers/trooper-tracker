<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\Issues;

use App\Models\Event;
use App\Models\EventShift;
use Database\Seeders\Issues\Fix246;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Fix246Test extends TestCase
{
    use RefreshDatabase;

    public function test_run_updates_shift_charity_from_legacy_table_and_prints_summary_breakdown(): void
    {
        $this->createLegacyEventsTable();

        $mapped_event = Event::factory()->create();
        EventShift::factory()->forEvent($mapped_event)->create([
            EventShift::ID => 100,
            EventShift::SHIFT_STARTS_AT => '2025-01-01 10:00:00',
            EventShift::SHIFT_ENDS_AT => '2025-01-01 13:00:00',
        ]);
        DB::table('events')->insert([
            'id' => 100,
            'charityDirectFunds' => 25,
            'charityIndirectFunds' => 5,
            'charityName' => 'Kids &amp; Co',
            'charityAddHours' => 2,
            'charityNote' => 'Bring extra smiles &amp; stickers',
        ]);

        $empty_mapped_event = Event::factory()->create();
        EventShift::factory()
            ->forEvent($empty_mapped_event)
            ->create([
                EventShift::ID => 101,
                EventShift::CHARITY_DIRECT_FUNDS => 40,
            ]);
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

        $this->artisan('db:seed', ['--class' => Fix246::class])
            ->expectsOutput('Florida Garrison event charity shift backfill')
            ->expectsOutput('Legacy shift charity mapping')
            ->expectsOutput('- Shifts scanned: 3')
            ->expectsOutput('- Legacy rows matched by shift id: 2')
            ->expectsOutput('- Shifts updated: 1')
            ->expectsOutput('- Updated with charity data: 1')
            ->expectsOutput('- Skipped, empty legacy charity data: 1')
            ->expectsOutput('- Skipped, no legacy row: 1')
            ->expectsOutput('- Skipped, legacy table unavailable: 0')
            ->expectsOutput('Done.')
            ->assertSuccessful();

        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 100,
            'charity_direct_funds' => 25,
            'charity_indirect_funds' => 5,
            'charity_name' => 'Kids & Co',
            'charity_hours' => 5, // 3-hour shift duration + 2 legacy offset
            'charity_notes' => 'Bring extra smiles & stickers',
        ]);
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => 101,
            'charity_direct_funds' => 40,
            'charity_indirect_funds' => 0,
            'charity_name' => null,
            'charity_hours' => null,
            'charity_notes' => null,
        ]);
    }

    public function test_run_without_legacy_table_skips_legacy_mapping(): void
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create([EventShift::ID => 200]);

        $this->assertFalse(Schema::hasTable('events'));

        $this->artisan('db:seed', ['--class' => Fix246::class])
            ->expectsOutput('Florida Garrison event charity shift backfill')
            ->expectsOutput('Legacy shift charity mapping')
            ->expectsOutput('- Shifts scanned: 1')
            ->expectsOutput('- Legacy rows matched by shift id: 0')
            ->expectsOutput('- Shifts updated: 0')
            ->expectsOutput('- Updated with charity data: 0')
            ->expectsOutput('- Skipped, empty legacy charity data: 0')
            ->expectsOutput('- Skipped, no legacy row: 1')
            ->expectsOutput('- Skipped, legacy table unavailable: 1')
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
}
