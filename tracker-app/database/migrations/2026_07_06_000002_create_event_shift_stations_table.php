<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_event_shift_stations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('event_shift_id')
                ->constrained('tt_event_shifts')
                ->cascadeOnDelete();

            $table->string('name', 128);
            $table->unsignedInteger('troopers_allowed');
            $table->unsignedInteger('sequence')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->index(['event_shift_id', 'sequence']);
        });

        //  stations always require a positive limit; SQLite cannot add a check
        //  constraint after create, so it relies on NOT NULL plus validation
        if (DB::getDriverName() === 'mysql')
        {
            DB::statement(
                'ALTER TABLE tt_event_shift_stations '
                .'ADD CONSTRAINT chk_station_troopers_allowed_min CHECK (troopers_allowed >= 1)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_event_shift_stations');
    }
};
