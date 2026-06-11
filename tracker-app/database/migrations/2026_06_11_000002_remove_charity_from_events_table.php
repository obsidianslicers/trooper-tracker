<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
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

    public function down(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->integer('charity_direct_funds')->default(0);
            $table->integer('charity_indirect_funds')->default(0);
            $table->string('charity_name', 128)->nullable();
            $table->integer('charity_hours')->nullable();
            $table->text('charity_notes')->nullable();
        });
    }
};
