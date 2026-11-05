<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composer-managed plugins from other repositories that an administrator added.
 * The deploy requires each one, since the app's composer.json never lists them.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_plugin_packages', function (Blueprint $table): void
        {
            $table->id();
            $table->string('name', 128)->unique();
            $table->string('version_constraint', 64)->nullable();

            $table->timestamps();
            $table->trooperstamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_plugin_packages');
    }
};
