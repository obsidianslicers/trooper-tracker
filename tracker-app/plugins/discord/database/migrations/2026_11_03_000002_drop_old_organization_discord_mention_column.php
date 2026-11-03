<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops core's old tt_organizations.discord_mention column once every value in it
 * is safely in the plugin's table.
 *
 * Each non-empty value is compared with its copy first. If any is missing or
 * different, the migration stops and leaves the column alone, so enabling the
 * plugin fails with a clear error instead of losing a mention. Rolling back (for
 * example on uninstall) adds the column back and refills it from the plugin table.
 */
return new class extends Migration {
    private const ORGANIZATIONS = 'tt_organizations';

    private const MENTIONS = 'tt_discord_organization_mentions';

    private const COLUMN = 'discord_mention';

    public function up(): void
    {
        if (!Schema::hasColumn(self::ORGANIZATIONS, self::COLUMN))
        {
            return;
        }

        $this->ensureEveryMentionWasCopied();

        Schema::table(self::ORGANIZATIONS, function (Blueprint $table): void
        {
            $table->dropColumn(self::COLUMN);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn(self::ORGANIZATIONS, self::COLUMN))
        {
            return;
        }

        Schema::table(self::ORGANIZATIONS, function (Blueprint $table): void
        {
            $table->string(self::COLUMN, 128)->nullable();
        });

        DB::table(self::MENTIONS)
            ->where('mention', '!=', '')
            ->orderBy('id')
            ->each(function (object $row): void
            {
                DB::table(self::ORGANIZATIONS)
                    ->where('id', $row->organization_id)
                    ->update([self::COLUMN => $row->mention]);
            });
    }

    private function ensureEveryMentionWasCopied(): void
    {
        $problems = DB::table(self::ORGANIZATIONS.' as organizations')
            ->leftJoin(
                self::MENTIONS.' as mentions',
                'mentions.organization_id',
                '=',
                'organizations.id',
            )
            ->whereNotNull('organizations.'.self::COLUMN)
            ->where('organizations.'.self::COLUMN, '!=', '')
            ->orderBy('organizations.id')
            ->get(['organizations.id', 'organizations.'.self::COLUMN.' as old', 'mentions.mention'])
            ->filter(fn (object $row): bool => $row->mention !== trim((string) $row->old))
            ->pluck('id');

        if ($problems->isNotEmpty())
        {
            throw new RuntimeException(
                'Kept tt_organizations.discord_mention: these organizations were not copied '
                .'correctly: '.$problems->implode(', ').'.'
            );
        }
    }
};
