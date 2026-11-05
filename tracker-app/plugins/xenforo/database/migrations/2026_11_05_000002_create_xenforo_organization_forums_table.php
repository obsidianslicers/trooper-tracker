<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves each organization's forum settings (event forum, archive forum, and the
 * user groups for active, reserve and retired members) out of core's
 * tt_organizations table into a table the XenForo plugin owns.
 *
 * The plugin never changes core tables: the old columns are left alone and are
 * dropped later by a core migration.
 */
return new class extends Migration {
    private const COLUMNS = [
        'related_forum' => 'event_forum_id',
        'related_forum_archive' => 'archive_forum_id',
        'xenforo_group_active_id' => 'active_group_id',
        'xenforo_group_reserve_id' => 'reserve_group_id',
        'xenforo_group_retired_id' => 'retired_group_id',
    ];

    public function up(): void
    {
        Schema::create('tt_xenforo_organization_forums', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('organization_id')
                ->unique()
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            foreach (self::COLUMNS as $column)
            {
                $table->unsignedBigInteger($column)->nullable();
            }

            $table->timestamps();
        });

        $this->copyExistingSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_xenforo_organization_forums');
    }

    private function copyExistingSettings(): void
    {
        $old_columns = array_keys(self::COLUMNS);

        if (!Schema::hasColumns('tt_organizations', $old_columns))
        {
            return;
        }

        DB::table('tt_organizations')
            ->select(['id', ...$old_columns])
            ->where(function ($query) use ($old_columns): void
            {
                foreach ($old_columns as $column)
                {
                    $query->orWhere($column, '>', 0);
                }
            })
            ->orderBy('id')
            ->chunk(500, function (Collection $organizations): void
            {
                $now = now();

                DB::table('tt_xenforo_organization_forums')->insert(
                    $organizations->map(fn (object $organization): array => [
                        'organization_id' => $organization->id,
                        ...$this->newValues($organization),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    /**
     * @return array<string, ?int>
     */
    private function newValues(object $organization): array
    {
        $values = [];

        foreach (self::COLUMNS as $old => $new)
        {
            $values[$new] = ((int) $organization->{$old}) > 0 ? (int) $organization->{$old} : null;
        }

        return $values;
    }
};
