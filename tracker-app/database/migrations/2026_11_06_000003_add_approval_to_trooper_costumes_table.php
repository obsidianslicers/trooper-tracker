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
        DB::table('tt_trooper_costumes')
            ->where(function ($query): void
            {
                $query->whereNotNull('synchronized_at')
                    ->orWhereNotExists(function ($synced_club): void
                    {
                        $synced_club->select(DB::raw(1))
                            ->from('tt_organization_costumes as oc')
                            ->join('tt_organizations as o', 'o.id', '=', 'oc.organization_id')
                            ->whereColumn('oc.id', 'tt_trooper_costumes.organization_costume_id')
                            ->whereNotNull('o.synchronizer');
                    });
            })
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
