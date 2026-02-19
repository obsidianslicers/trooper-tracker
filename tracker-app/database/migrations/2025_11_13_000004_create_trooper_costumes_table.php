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
        Schema::create('tt_trooper_costumes', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();
            $table->foreignId('organization_costume_id')
                ->constrained('tt_organization_costumes')
                ->cascadeOnDelete();

            $table->string('image_url_sm', 128)->nullable();
            $table->string('image_url_lg', 128)->nullable();
            $table->string('image_url_bucket_off', 128)->nullable();
            $table->dateTime('synchronized_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            // Prevent duplicate entries
            $table->unique(columns: ['trooper_id', 'organization_costume_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_costumes');
    }
};
