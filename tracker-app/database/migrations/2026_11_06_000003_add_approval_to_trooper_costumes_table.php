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

        //  existing costumes are trusted, except self-added costumes in clubs that sync
        //  from an API: those were never confirmed by the club and wait for an officer
        $unconfirmed_synced_ids = DB::table('tt_trooper_costumes as tc')
            ->join('tt_organization_costumes as oc', 'oc.id', '=', 'tc.organization_costume_id')
            ->join('tt_organizations as o', 'o.id', '=', 'oc.organization_id')
            ->whereNull('tc.synchronized_at')
            ->whereNotNull('o.synchronizer')
            ->pluck('tc.id');

        DB::table('tt_trooper_costumes')
            ->whereNotIn('id', $unconfirmed_synced_ids)
            ->update([
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
