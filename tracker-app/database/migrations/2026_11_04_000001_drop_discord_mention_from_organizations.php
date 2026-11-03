<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops tt_organizations.discord_mention now that the Discord plugin owns the
 * mentions. Ship this in a release after production has the plugin enabled.
 *
 * Any value the plugin table is missing is copied across first. If a site has
 * mentions but never enabled the Discord plugin, this stops instead of dropping
 * the column, so no mention is lost.
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

        if (Schema::hasTable(self::MENTIONS))
        {
            $this->copyMissingMentions();
        }
        elseif ($this->oldMentions()->exists())
        {
            throw new RuntimeException(
                'tt_organizations.discord_mention still has values and the Discord plugin '
                .'was never enabled. Run "php artisan plugins:enable discord", then migrate again.'
            );
        }

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
    }

    private function copyMissingMentions(): void
    {
        $this->oldMentions()
            ->whereNotIn('id', DB::table(self::MENTIONS)->select('organization_id'))
            ->chunkById(500, function (Collection $organizations): void
            {
                $now = now();

                DB::table(self::MENTIONS)->insert($organizations->map(
                    fn (object $organization): array => [
                        'organization_id' => $organization->id,
                        'mention' => trim((string) $organization->discord_mention),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                )->all());
            });
    }

    private function oldMentions(): Builder
    {
        return DB::table(self::ORGANIZATIONS)
            ->select(['id', self::COLUMN])
            ->whereNotNull(self::COLUMN)
            ->where(self::COLUMN, '!=', '');
    }
};
