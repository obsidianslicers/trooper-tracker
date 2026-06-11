<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->integer('charity_direct_funds')->default(0)->after('last_notified_at');
            $table->integer('charity_indirect_funds')->default(0)->after('charity_direct_funds');
            $table->string('charity_name', 128)->nullable()->after('charity_indirect_funds');
            $table->integer('charity_hours')->nullable()->after('charity_name');
            $table->text('charity_notes')->nullable()->after('charity_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->dropColumn([
                'charity_direct_funds',
                'charity_indirect_funds',
                'charity_name',
                'charity_hours',
                'charity_notes',
            ]);
        });
    }
};
