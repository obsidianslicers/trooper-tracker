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
        Schema::create('tt_trooper_friends', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->foreignId('friend_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate entries
            $table->unique(['trooper_id', 'friend_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_friends');
    }
};
