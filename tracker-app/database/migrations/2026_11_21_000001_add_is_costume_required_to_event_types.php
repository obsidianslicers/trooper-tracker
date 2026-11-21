<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Whether troopers wear a costume to an event type's events. When they don't (an Armor Party,
 * a social), sign-up asks for no costume and the event shows none.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tt_event_types', function (Blueprint $table)
        {
            $table->boolean('is_costume_required')->default(true)->after('is_counted');
        });

        //  the one seeded type that's a gathering rather than an appearance
        DB::table('tt_event_types')->where('name', 'Armor Party')->update(['is_costume_required' => false]);
    }

    public function down(): void
    {
        Schema::table('tt_event_types', function (Blueprint $table)
        {
            $table->dropColumn('is_costume_required');
        });
    }
};
