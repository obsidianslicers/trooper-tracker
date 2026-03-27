<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_trooper_api_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trooper_id')->constrained('tt_troopers')->cascadeOnDelete();
            $table->string('api_code', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_api_codes');
    }
};
