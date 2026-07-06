<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_event_shift_stations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('event_shift_id')
                ->constrained('tt_event_shifts')
                ->cascadeOnDelete();

            $table->string('name', 128);
            $table->unsignedInteger('troopers_allowed');
            $table->unsignedInteger('sequence')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->index(['event_shift_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_event_shift_stations');
    }
};
