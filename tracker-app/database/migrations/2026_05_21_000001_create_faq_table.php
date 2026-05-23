<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_faq_sections', function (Blueprint $table)
        {
            $table->id();

            $table->text('label');
            $table->string('icon', 64);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });

        Schema::create('tt_faq', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('section_id')->constrained('tt_faq_sections')->cascadeOnDelete();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('video_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_faq');
        Schema::dropIfExists('tt_faq_sections');
    }
};
