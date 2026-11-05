<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_plugins', function (Blueprint $table)
        {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('source', 16)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_enable_pending')->default(false);
            $table->string('status', 16)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->string('installed_version', 32)->nullable();
            $table->text('settings')->nullable();
            $table->timestamp('enabled_at')->nullable();

            $table->timestamps();
            $table->trooperstamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_plugins');
    }
};
