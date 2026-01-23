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
        Schema::create('tt_model_changes', function (Blueprint $table)
        {
            $table->id();

            // Polymorphic link to the model being changed
            $table->morphs('auditable');

            $table->foreignId('trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->nullOnDelete();

            // What changed 
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->timestamps();
            $table->trooperstamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_model_changes');
    }
};
