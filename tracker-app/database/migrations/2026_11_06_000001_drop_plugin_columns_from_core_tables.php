<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the core columns the Discord and XenForo plugins copied into their own
 * tables when they were first enabled. Core no longer reads them.
 *
 * Any row the plugin table is missing is copied across first. If a site has data
 * in a column but never enabled the plugin that owns it, this stops before
 * dropping anything, so nothing is lost.
 */
return new class extends Migration {
    private const FORUM_COLUMNS = [
        'related_forum',
        'related_forum_archive',
        'xenforo_group_active_id',
        'xenforo_group_reserve_id',
        'xenforo_group_retired_id',
    ];

    private const FORUM_COLUMN_MAP = [
        'related_forum' => 'event_forum_id',
        'related_forum_archive' => 'archive_forum_id',
        'xenforo_group_active_id' => 'active_group_id',
        'xenforo_group_reserve_id' => 'reserve_group_id',
        'xenforo_group_retired_id' => 'retired_group_id',
    ];

    public function up(): void
    {
        $groups = array_filter(
            $this->groups(),
            fn (array $group): bool => Schema::hasColumns($group['table'], $group['columns']),
        );

        foreach ($groups as $group)
        {
            $this->ensureNothingIsLost($group);
        }

        foreach ($groups as $group)
        {
            if (Schema::hasTable($group['plugin_table']))
            {
                $group['copy']();
            }
        }

        foreach ($groups as $group)
        {
            $this->dropColumns($group['table'], $group['columns']);
        }
    }

    public function down(): void
    {
        $this->restoreOrganizationColumns();
        $this->restoreEventColumns();
        $this->restorePermissionRequestColumns();
    }

    /**
     * @return array<int, array{table: string, columns: array<int, string>,
     *     plugin: string, plugin_table: string, has_data: Closure, copy: Closure}>
     */
    private function groups(): array
    {
        return [
            [
                'table' => 'tt_organizations',
                'columns' => ['discord_mention'],
                'plugin' => 'discord',
                'plugin_table' => 'tt_discord_organization_mentions',
                'has_data' => fn (): bool => $this->oldMentions()->exists(),
                'copy' => fn () => $this->copyMentions(),
            ],
            [
                'table' => 'tt_organizations',
                'columns' => self::FORUM_COLUMNS,
                'plugin' => 'xenforo',
                'plugin_table' => 'tt_xenforo_organization_forums',
                'has_data' => fn (): bool => $this->oldForums()->exists(),
                'copy' => fn () => $this->copyForums(),
            ],
            [
                'table' => 'tt_events',
                'columns' => ['create_forum_thread', 'thread_id', 'post_id'],
                'plugin' => 'xenforo',
                'plugin_table' => 'tt_xenforo_event_threads',
                'has_data' => fn (): bool => $this->oldThreads()->exists(),
                'copy' => fn () => $this->copyThreads(),
            ],
            [
                'table' => 'tt_permission_requests',
                'columns' => ['forum_groups_to_add', 'forum_groups_to_remove'],
                'plugin' => 'xenforo',
                'plugin_table' => 'tt_xenforo_permission_request_groups',
                'has_data' => fn (): bool => $this->requestsWithGroups()->isNotEmpty(),
                'copy' => fn () => $this->copyGroups(),
            ],
        ];
    }

    /**
     * @param  array{table: string, columns: array<int, string>, plugin: string,
     *     plugin_table: string, has_data: Closure, copy: Closure}  $group
     */
    private function ensureNothingIsLost(array $group): void
    {
        if (Schema::hasTable($group['plugin_table']) || !$group['has_data']())
        {
            return;
        }

        $columns = implode(', ', $group['columns']);

        throw new RuntimeException(
            "{$group['table']} ({$columns}) still has data and the {$group['plugin']} plugin "
            ."was never enabled. Run \"php artisan plugins:enable {$group['plugin']}\" and "
            .'"php artisan plugins:reconcile", then migrate again.'
        );
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropColumns(string $table, array $columns): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($columns): void
        {
            $blueprint->dropColumn($columns);
        });
    }

    private function oldMentions(): Builder
    {
        return DB::table('tt_organizations')
            ->whereNotNull('discord_mention')
            ->where('discord_mention', '!=', '');
    }

    private function copyMentions(): void
    {
        $this->oldMentions()
            ->select(['id', 'discord_mention'])
            ->whereNotIn(
                'id',
                DB::table('tt_discord_organization_mentions')->select('organization_id')
            )
            ->orderBy('id')
            ->each(function (object $organization): void
            {
                DB::table('tt_discord_organization_mentions')->insert([
                    'organization_id' => $organization->id,
                    'mention' => trim((string) $organization->discord_mention),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function oldForums(): Builder
    {
        return DB::table('tt_organizations')->where(function ($query): void
        {
            foreach (self::FORUM_COLUMNS as $column)
            {
                $query->orWhere($column, '>', 0);
            }
        });
    }

    private function copyForums(): void
    {
        $this->oldForums()
            ->select(['id', ...self::FORUM_COLUMNS])
            ->whereNotIn(
                'id',
                DB::table('tt_xenforo_organization_forums')->select('organization_id')
            )
            ->orderBy('id')
            ->each(function (object $organization): void
            {
                $row = ['organization_id' => $organization->id];

                foreach (self::FORUM_COLUMN_MAP as $old => $new)
                {
                    $value = (int) $organization->{$old};
                    $row[$new] = $value > 0 ? $value : null;
                }

                DB::table('tt_xenforo_organization_forums')->insert(
                    $row + ['created_at' => now(), 'updated_at' => now()]
                );
            });
    }

    private function oldThreads(): Builder
    {
        return DB::table('tt_events')->where(fn ($query) => $query
            ->where('thread_id', '>', 0)
            ->orWhere('create_forum_thread', false));
    }

    private function copyThreads(): void
    {
        $this->oldThreads()
            ->select(['id', 'thread_id', 'post_id', 'create_forum_thread'])
            ->whereNotIn('id', DB::table('tt_xenforo_event_threads')->select('event_id'))
            ->orderBy('id')
            ->each(function (object $event): void
            {
                DB::table('tt_xenforo_event_threads')->insert([
                    'event_id' => $event->id,
                    'should_create_thread' => (bool) $event->create_forum_thread,
                    'thread_id' => $event->thread_id ?: null,
                    'post_id' => $event->post_id ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    /**
     * Requests that ask for at least one forum group and have no plugin row yet.
     *
     * @return Collection<int, object>
     */
    private function requestsWithGroups(): Collection
    {
        $query = DB::table('tt_permission_requests')
            ->select(['id', 'forum_groups_to_add', 'forum_groups_to_remove'])
            ->where(fn ($query) => $query
                ->whereNotNull('forum_groups_to_add')
                ->orWhereNotNull('forum_groups_to_remove'))
            ->orderBy('id');

        if (Schema::hasTable('tt_xenforo_permission_request_groups'))
        {
            $query->whereNotIn(
                'id',
                DB::table('tt_xenforo_permission_request_groups')->select('permission_request_id')
            );
        }

        return $query->get()->filter(fn (object $request): bool => $this->hasGroups($request));
    }

    private function hasGroups(object $request): bool
    {
        foreach ([$request->forum_groups_to_add, $request->forum_groups_to_remove] as $json)
        {
            $groups = json_decode((string) $json, true);

            if (is_array($groups) && $groups !== [])
            {
                return true;
            }
        }

        return false;
    }

    private function copyGroups(): void
    {
        foreach ($this->requestsWithGroups() as $request)
        {
            DB::table('tt_xenforo_permission_request_groups')->insert([
                'permission_request_id' => $request->id,
                'groups_to_add' => $request->forum_groups_to_add,
                'groups_to_remove' => $request->forum_groups_to_remove,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function restoreOrganizationColumns(): void
    {
        Schema::table('tt_organizations', function (Blueprint $table): void
        {
            if (!Schema::hasColumn('tt_organizations', 'discord_mention'))
            {
                $table->string('discord_mention', 128)->nullable();
            }

            foreach (self::FORUM_COLUMNS as $column)
            {
                if (!Schema::hasColumn('tt_organizations', $column))
                {
                    $table->unsignedBigInteger($column)->nullable();
                }
            }
        });
    }

    private function restoreEventColumns(): void
    {
        if (Schema::hasColumn('tt_events', 'thread_id'))
        {
            return;
        }

        Schema::table('tt_events', function (Blueprint $table): void
        {
            $table->boolean('create_forum_thread')->default(true);
            $table->integer('thread_id')->nullable();
            $table->integer('post_id')->nullable();
        });
    }

    private function restorePermissionRequestColumns(): void
    {
        if (Schema::hasColumn('tt_permission_requests', 'forum_groups_to_add'))
        {
            return;
        }

        Schema::table('tt_permission_requests', function (Blueprint $table): void
        {
            $table->json('forum_groups_to_add')->nullable();
            $table->json('forum_groups_to_remove')->nullable();
        });
    }
};
