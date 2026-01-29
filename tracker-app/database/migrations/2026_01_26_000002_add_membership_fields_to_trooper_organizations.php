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
        Schema::table('tt_trooper_organizations', function (Blueprint $table) {
            $table->date('joined_at')->nullable()->after('identifier');
            $table->string('display_name', 128)->nullable()->after('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_trooper_organizations', function (Blueprint $table) {
            $table->dropColumn(['joined_at', 'display_name']);
        });
    }
};
