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
        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->index('trooper_id', 'tt_trooper_achievements_trooper_id_index');
        });

        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->dropUnique(['trooper_id', 'type']);

            $table->foreignId('organization_id')
                ->nullable()
                ->after('trooper_id')
                ->constrained('tt_organizations')
                ->nullOnDelete();

            $table->unsignedBigInteger('organization_coalesce_id')
                ->virtualAs('coalesce(organization_id, 0)')
                ->after('organization_id');

            $table->unique(
                ['trooper_id', 'type', 'organization_coalesce_id'],
                'tt_trooper_achievements_coalesce_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->dropUnique('tt_trooper_achievements_coalesce_unique');
        });

        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->dropColumn('organization_coalesce_id');
            $table->dropConstrainedForeignId('organization_id');

            $table->unique(['trooper_id', 'type']);
        });

        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->dropIndex('tt_trooper_achievements_trooper_id_index');
        });
    }
};
