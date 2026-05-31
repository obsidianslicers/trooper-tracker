<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_event_watches', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();
            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['event_id', 'trooper_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_event_watches');
    }
};
