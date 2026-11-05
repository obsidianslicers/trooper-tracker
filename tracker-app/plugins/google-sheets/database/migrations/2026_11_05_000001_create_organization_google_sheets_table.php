<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves each club's Google Sheet ID out of core's tt_organizations table into a
 * table the Google Sheets plugin owns.
 *
 * The plugin never changes core tables: tt_organizations.sync_sheet_id is left
 * alone and is dropped later by a core migration.
 */
return new class extends Migration {
    private const TABLE = 'tt_organization_google_sheets';

    public function up(): void
    {
        // MySQL can't roll back DDL, so a failed earlier run may have left this behind.
        Schema::dropIfExists(self::TABLE);

        Schema::create(self::TABLE, function (Blueprint $table)
        {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unique('organization_id', 'tt_org_google_sheets_organization_unique');
            $table->foreign('organization_id', 'tt_org_google_sheets_organization_foreign')
                ->references('id')
                ->on('tt_organizations')
                ->cascadeOnDelete();
            $table->string('sheet_id', 128);
            $table->timestamps();
        });

        $this->copyExistingSheets();
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }

    private function copyExistingSheets(): void
    {
        if (!Schema::hasColumn('tt_organizations', 'sync_sheet_id'))
        {
            return;
        }

        DB::table('tt_organizations')
            ->select(['id', 'sync_sheet_id'])
            ->whereNotNull('sync_sheet_id')
            ->where('sync_sheet_id', '!=', '')
            ->orderBy('id')
            ->chunk(500, function (Collection $organizations): void
            {
                $now = now();

                DB::table(self::TABLE)->insert(
                    $organizations->map(fn (object $organization): array => [
                        'organization_id' => $organization->id,
                        'sheet_id' => trim((string) $organization->sync_sheet_id),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }
};
