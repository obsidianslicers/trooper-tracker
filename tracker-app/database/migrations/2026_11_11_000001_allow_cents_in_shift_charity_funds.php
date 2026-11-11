<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a shift's direct and indirect funds hold cents (TT 1.0 stored them as decimal(13,2)).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->decimal('charity_direct_funds', 13, 2)->default(0)->change();
            $table->decimal('charity_indirect_funds', 13, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->integer('charity_direct_funds')->default(0)->change();
            $table->integer('charity_indirect_funds')->default(0)->change();
        });
    }
};
