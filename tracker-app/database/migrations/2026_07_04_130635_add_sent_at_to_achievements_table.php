<?php

use App\Models\TrooperAchievement;
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
            $table->timestamp('notification_sent_at')->nullable();
        });

        //  default the notification_sent_at column for all achievements with a date in the past
        TrooperAchievement::query()
            ->where(TrooperAchievement::ACHIEVEMENT_DATE, '<', today())
            ->update(['notification_sent_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_trooper_achievements', function (Blueprint $table)
        {
            $table->dropColumn('notification_sent_at');
        });
    }
};