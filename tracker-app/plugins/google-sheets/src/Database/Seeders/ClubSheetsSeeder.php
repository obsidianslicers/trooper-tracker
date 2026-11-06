<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets\Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use TroopTracker\Plugins\GoogleSheets\Models\OrganizationSheet;

/**
 * The Google Sheets the clubs' synchronizers read their rosters from.
 *
 * Run after the organization seeder, with the Google Sheets plugin enabled:
 *   php artisan db:seed \
 *     --class="TroopTracker\Plugins\GoogleSheets\Database\Seeders\ClubSheetsSeeder"
 */
class ClubSheetsSeeder extends Seeder
{
    private const SHEETS = [
        'Rebel Legion' => '1I3FuS_uPg2nuC80PEA6tKYaVBd1Qh1allTOdVz3M6x0',
        'Droid Builders' => '195NT1crFYL_ECVyzoaD2F1QXGW5WxlnBDfDaLVtM87Y',
        'Saber Guild' => '1PcveycMujakkKeG2m4y8iFunrFbo2KVpQJ00GyPI3b8',
    ];

    public function run(): void
    {
        $clubs = Organization::query()
            ->whereNull(Organization::PARENT_ID)
            ->whereIn(Organization::NAME, array_keys(self::SHEETS))
            ->get();

        foreach ($clubs as $club)
        {
            OrganizationSheet::store($club->id, self::SHEETS[$club->name]);
        }
    }
}
