<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table): void {
            $table->foreignId('event_shift_station_id')
                ->nullable()
                ->after('event_shift_id')
                ->constrained('tt_event_shift_stations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('event_shift_station_id');
        });
    }
};
