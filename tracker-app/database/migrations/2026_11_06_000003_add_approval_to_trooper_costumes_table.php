<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tt_trooper_costumes', function (Blueprint $table)
        {
            $table->timestamp('approved_at')->nullable()->after('synchronized_at');
            $table->unsignedBigInteger('approved_id')->nullable()->after('approved_at');
            $table->index('approved_at');
        });

        //  every costume a trooper already has is approved; the gate only covers new adds
        DB::table('tt_trooper_costumes')->update([
            'approved_at' => DB::raw('COALESCE(synchronized_at, created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_trooper_costumes', function (Blueprint $table)
        {
            $table->dropIndex(['approved_at']);
            $table->dropColumn(['approved_at', 'approved_id']);
        });
    }
};
