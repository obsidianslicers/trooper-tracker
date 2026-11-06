<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_plugin_changes', function (Blueprint $table)
        {
            $table->id();
            $table->string('slug', 64)->index();
            $table->string('action', 32);
            $table->string('from_version', 32)->nullable();
            $table->string('to_version', 32)->nullable();
            $table->json('migrations')->nullable();
            $table->text('message')->nullable();

            $table->timestamps();
            $table->trooperstamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_plugin_changes');
    }
};
