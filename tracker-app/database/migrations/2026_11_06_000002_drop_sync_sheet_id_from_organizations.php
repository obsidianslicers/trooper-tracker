<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops tt_organizations.sync_sheet_id, which the Google Sheets plugin copied into
 * tt_organization_google_sheets when it was first enabled. Core no longer reads it.
 *
 * Any sheet the plugin table is missing is copied across first. If a site has sheet
 * IDs but never enabled the plugin, this stops instead of dropping, so none is lost.
 */
return new class extends Migration {
    private const ORGANIZATIONS = 'tt_organizations';

    private const SHEETS = 'tt_organization_google_sheets';

    private const COLUMN = 'sync_sheet_id';

    public function up(): void
    {
        if (!Schema::hasColumn(self::ORGANIZATIONS, self::COLUMN))
        {
            return;
        }

        if (Schema::hasTable(self::SHEETS))
        {
            $this->copyMissingSheets();
        }
        elseif ($this->oldSheets()->exists())
        {
            throw new RuntimeException(
                'tt_organizations.sync_sheet_id still has values and the google-sheets plugin '
                .'was never enabled. Run "php artisan plugins:enable google-sheets" and deploy '
                .'(its Composer packages install then), then migrate again.'
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

    private function copyMissingSheets(): void
    {
        $this->oldSheets()
            ->select(['id', self::COLUMN])
            ->whereNotIn('id', DB::table(self::SHEETS)->select('organization_id'))
            ->orderBy('id')
            ->each(function (object $organization): void
            {
                DB::table(self::SHEETS)->insert([
                    'organization_id' => $organization->id,
                    'sheet_id' => trim((string) $organization->sync_sheet_id),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function oldSheets(): Builder
    {
        return DB::table(self::ORGANIZATIONS)
            ->whereNotNull(self::COLUMN)
            ->whereRaw('trim('.self::COLUMN.") != ''");
    }
};
