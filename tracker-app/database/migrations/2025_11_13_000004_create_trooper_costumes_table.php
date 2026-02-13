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
            $table->foreignId('costume_id')
                ->constrained('tt_organization_costumes')
                ->cascadeOnDelete();
            $table->string('costume_prefix')
                ->nullable()
                ->after('costume_id');
            $table->string('small_image_url')
                ->nullable()
                ->after('costume_prefix');
            $table->string('large_image_url')
                ->nullable()->after('small_image_url');
            $table->string('bucket_off_url')
                ->nullable()
                ->after('large_image_url');


            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            // Prevent duplicate entries
            $table->unique(columns: ['trooper_id', 'costume_id']);
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
