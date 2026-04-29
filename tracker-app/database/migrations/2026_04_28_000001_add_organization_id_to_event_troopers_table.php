<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('trooper_id')
                ->constrained('tt_organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->dropForeignIdFor(null, 'organization_id');
            $table->dropColumn('organization_id');
        });
    }
};
