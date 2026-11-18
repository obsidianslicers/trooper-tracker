<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The attendee picked as an event's coordinator, when it isn't whoever created the event.
 * Null means the creator coordinates it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->foreignId('coordinator_trooper_id')
                ->nullable()
                ->after('event_type_id')
                ->constrained('tt_troopers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropConstrainedForeignId('coordinator_trooper_id');
        });
    }
};
