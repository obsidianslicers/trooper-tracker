<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trooper_id')->nullable()->constrained('tt_troopers')->nullOnDelete();
            $table->string('fcm_token')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_mobile_devices');
    }
};
