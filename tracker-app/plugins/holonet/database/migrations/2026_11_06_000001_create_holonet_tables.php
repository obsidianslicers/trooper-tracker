<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a trooper makes to their accounts on other trackers (and the troops
 * copied from them), and the access this tracker has given other trackers.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_trooper_holonet_links', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('trooper_id')->constrained('tt_troopers')->cascadeOnDelete();
            $table->string('tracker_url', 255);
            $table->string('tracker_name', 128);
            $table->string('remote_display_name', 128)->nullable();
            $table->text('token');
            $table->string('status', 16)->default('active');
            $table->dateTime('refreshed_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
            $table->unique(['trooper_id', 'tracker_url']);
        });

        Schema::create('tt_trooper_holonet_troops', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('holonet_link_id')
                ->constrained('tt_trooper_holonet_links')
                ->cascadeOnDelete();
            $table->string('remote_id', 64);
            $table->string('event_name', 256);
            $table->string('event_type', 32)->nullable();
            $table->string('venue', 256)->nullable();
            $table->dateTime('shift_starts_at');
            $table->dateTime('shift_ends_at');
            $table->string('costume_name', 256)->nullable();
            $table->json('clubs')->nullable();
            $table->boolean('is_handler')->default(false);
            $table->timestamps();
            $table->unique(['holonet_link_id', 'remote_id'], 'tt_trooper_holonet_troops_link_remote_unique');
            $table->index('shift_starts_at');
        });

        Schema::create('tt_trooper_holonet_grants', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('trooper_id')->constrained('tt_troopers')->cascadeOnDelete();
            $table->string('tracker_url', 255);
            $table->string('tracker_name', 128);
            $table->string('token_hash', 64)->unique();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['trooper_id', 'tracker_url']);
        });

        Schema::create('tt_trooper_holonet_codes', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('trooper_id')->constrained('tt_troopers')->cascadeOnDelete();
            $table->string('tracker_url', 255);
            $table->string('code_hash', 64)->unique();
            $table->string('code_challenge', 128);
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_holonet_codes');
        Schema::dropIfExists('tt_trooper_holonet_grants');
        Schema::dropIfExists('tt_trooper_holonet_troops');
        Schema::dropIfExists('tt_trooper_holonet_links');
    }
};
