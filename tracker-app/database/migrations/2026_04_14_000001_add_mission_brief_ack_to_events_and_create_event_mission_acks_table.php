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
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->boolean('require_mission_brief_ack')->default(false);
        });

        Schema::create('tt_event_mission_acks', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->dateTime('acknowledged_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->unique(['event_id', 'trooper_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_event_mission_acks');

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropColumn('require_mission_brief_ack');
        });
    }
};
