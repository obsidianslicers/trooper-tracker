<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->boolean('is_attending_without_costume')->default(false)->after('costume_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->dropColumn('is_attending_without_costume');
        });
    }
};
