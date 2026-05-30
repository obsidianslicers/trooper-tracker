<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table) {
            $table->unsignedBigInteger('forum_display_costume_id')
                ->nullable()
                ->after('notification_preferences');

            $table->foreign('forum_display_costume_id')
                ->references('id')
                ->on('tt_trooper_costumes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table) {
            $table->dropForeign(['forum_display_costume_id']);
            $table->dropColumn('forum_display_costume_id');
        });
    }
};
