<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
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
        Schema::create('tt_event_shifts', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();

            $table->string('status', 16)->default(EventStatus::DRAFT->value);
            $table->dateTime('shift_starts_at')->nullable();
            $table->dateTime('shift_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            // Prevent duplicate entries
            $table->unique(['event_id', 'shift_starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_event_shifts');
    }
};