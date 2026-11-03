<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Discord notifications moved out of core into the Discord plugin. Sites that
 * already had a webhook configured keep getting announcements without having
 * to enable the plugin by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (empty(env('DISCORD_WEBHOOK_URL')))
        {
            return;
        }

        DB::table('tt_plugins')->updateOrInsert(
            ['slug' => 'discord'],
            [
                'is_enabled' => true,
                'installed_version' => '1.0.0',
                'enabled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Cache::forget('plugins.state');
    }

    public function down(): void
    {
        DB::table('tt_plugins')->where('slug', 'discord')->delete();

        Cache::forget('plugins.state');
    }
};
