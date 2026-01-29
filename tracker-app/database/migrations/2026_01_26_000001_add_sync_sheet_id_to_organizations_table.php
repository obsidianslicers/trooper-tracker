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
        Schema::table('tt_organizations', function (Blueprint $table) {
            $table->string('sync_sheet_id', 128)->nullable()->after('service_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_organizations', function (Blueprint $table) {
            $table->dropColumn('sync_sheet_id');
        });
    }
};
