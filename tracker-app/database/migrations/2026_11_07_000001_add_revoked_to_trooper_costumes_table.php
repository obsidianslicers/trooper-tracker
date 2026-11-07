<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tt_trooper_costumes', function (Blueprint $table)
        {
            $table->timestamp('revoked_at')->nullable()->after('approved_id');
            $table->unsignedBigInteger('revoked_id')->nullable()->after('revoked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_trooper_costumes', function (Blueprint $table)
        {
            $table->dropColumn(['revoked_at', 'revoked_id']);
        });
    }
};
