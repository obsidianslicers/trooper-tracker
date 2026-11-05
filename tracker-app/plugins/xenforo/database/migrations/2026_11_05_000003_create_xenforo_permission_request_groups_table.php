<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the forum groups each permission request asks for out of core's
 * tt_permission_requests table into a table the XenForo plugin owns.
 *
 * The plugin never changes core tables: the old columns are left alone and are
 * dropped later by a core migration.
 */
return new class extends Migration {
    public function up(): void
    {
        // An earlier attempt on MySQL could leave the table behind without
        // recording this migration, because MySQL can't roll back DDL.
        Schema::dropIfExists('tt_xenforo_permission_request_groups');

        // Named by hand: the generated names are longer than MySQL's 64 characters.
        Schema::create('tt_xenforo_permission_request_groups', function (Blueprint $table)
        {
            $table->id();
            $table->unsignedBigInteger('permission_request_id');
            $table->unique('permission_request_id', 'tt_xf_perm_req_groups_request_unique');
            $table->foreign('permission_request_id', 'tt_xf_perm_req_groups_request_foreign')
                ->references('id')
                ->on('tt_permission_requests')
                ->cascadeOnDelete();
            $table->json('groups_to_add')->nullable();
            $table->json('groups_to_remove')->nullable();
            $table->timestamps();
        });

        $this->copyExistingGroups();
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_xenforo_permission_request_groups');
    }

    private function copyExistingGroups(): void
    {
        $columns = ['forum_groups_to_add', 'forum_groups_to_remove'];

        if (! Schema::hasColumns('tt_permission_requests', $columns))
        {
            return;
        }

        DB::table('tt_permission_requests')
            ->select(['id', 'forum_groups_to_add', 'forum_groups_to_remove'])
            ->where(fn ($query) => $query
                ->whereNotNull('forum_groups_to_add')
                ->orWhereNotNull('forum_groups_to_remove'))
            ->orderBy('id')
            ->chunk(500, function (Collection $requests): void
            {
                $now = now();
                $rows = $requests
                    ->filter(fn (object $request): bool => $this->hasGroups($request))
                    ->map(fn (object $request): array => [
                        'permission_request_id' => $request->id,
                        'groups_to_add' => $request->forum_groups_to_add,
                        'groups_to_remove' => $request->forum_groups_to_remove,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                if ($rows->isNotEmpty())
                {
                    DB::table('tt_xenforo_permission_request_groups')
                        ->insert($rows->values()->all());
                }
            });
    }

    private function hasGroups(object $request): bool
    {
        foreach (['forum_groups_to_add', 'forum_groups_to_remove'] as $column)
        {
            $groups = json_decode((string) $request->{$column}, true);

            if (is_array($groups) && $groups !== [])
            {
                return true;
            }
        }

        return false;
    }
};
