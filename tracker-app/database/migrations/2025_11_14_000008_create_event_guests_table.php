<?php

use App\Enums\EventTrooperStatus;
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
        Schema::create('tt_event_guests', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_shift_id')
                ->constrained('tt_event_shifts')
                ->cascadeOnDelete();

            $table->foreignId('added_by_trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->string('name', 128);
            $table->boolean('is_handler')->default(false);
            $table->string('status', 16)->default(EventTrooperStatus::NONE->value);
            $table->dateTime('signed_up_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            // Prevent duplicate entries
            $table->unique(columns: ['event_shift_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_event_guests');
    }
};
