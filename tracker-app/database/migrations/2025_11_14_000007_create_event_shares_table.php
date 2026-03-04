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
        Schema::create('tt_event_shares', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->uuid('share_token')->unique();
            $table->string('recipient_email', 128);
            $table->integer('view_count')->default(0);
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_event_shares');
    }
};
