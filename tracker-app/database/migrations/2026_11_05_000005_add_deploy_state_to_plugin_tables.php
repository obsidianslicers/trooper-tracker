<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What each deploy found for every plugin, so the Plugins page can say whether it
 * is working, waiting, failed or missing, and how its Composer package updates.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tt_plugins', function (Blueprint $table): void
        {
            $table->string('source', 16)->nullable()->after('slug');
            $table->string('status', 16)->nullable()->after('is_enable_pending');
            $table->text('last_error')->nullable()->after('status');
            $table->timestamp('checked_at')->nullable()->after('last_error');
        });

        Schema::table('tt_plugin_packages', function (Blueprint $table): void
        {
            $table->string('repository_url', 255)->nullable()->after('version_constraint');
            $table->string('update_policy', 16)->default('pinned')->after('repository_url');
            $table->string('installed_version', 64)->nullable()->after('update_policy');
            $table->text('last_error')->nullable()->after('installed_version');
            $table->timestamp('checked_at')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('tt_plugins', function (Blueprint $table): void
        {
            $table->dropColumn(['source', 'status', 'last_error', 'checked_at']);
        });

        Schema::table('tt_plugin_packages', function (Blueprint $table): void
        {
            $table->dropColumn([
                'repository_url',
                'update_policy',
                'installed_version',
                'last_error',
                'checked_at',
            ]);
        });
    }
};
