<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stations must always have a positive numerical limit. The column is already
 * non-nullable; this adds a database-level floor of 1 where the driver
 * supports adding check constraints in place (MySQL). SQLite cannot add check
 * constraints to existing tables, so the test database relies on the NOT NULL
 * column plus request and handler validation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql')
        {
            return;
        }

        DB::statement(
            'ALTER TABLE tt_event_shift_stations '
            .'ADD CONSTRAINT chk_station_troopers_allowed_min CHECK (troopers_allowed >= 1)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql')
        {
            return;
        }

        DB::statement(
            'ALTER TABLE tt_event_shift_stations '
            .'DROP CONSTRAINT chk_station_troopers_allowed_min'
        );
    }
};
