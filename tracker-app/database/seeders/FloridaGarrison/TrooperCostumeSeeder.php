<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasCostumeMaps;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Extension\SmartPunct\EllipsesParser;

class TrooperCostumeSeeder extends Seeder
{
    use HasEnumMaps;
    use HasClubMaps;
    use HasCostumeMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $club_map = collect($this->getCostumeClubMap());

        $legacy_costumes = $this->getMappedLegacyCostumes();

        $this->migrateFavoriteCostumes($club_map, $legacy_costumes);
        $this->migrateEventTrooperCostumes($club_map, $legacy_costumes);
    }

    private function migrateFavoriteCostumes($club_map, $legacy_costumes): void
    {
        $favorite_costumes = DB::table('favorite_costumes')
            ->join('costumes', 'costumes.id', '=', 'favorite_costumes.costumeid')
            ->whereRaw('costumes.club IS NOT NULL AND costumes.club != 4')
            ->whereRaw("costumes.costume not in ('NA', 'N/A', 'Handler')")
            ->selectRaw('favorite_costumes.*, costumes.costume, costumes.club')
            ->get();

        foreach ($favorite_costumes as $favorite_costume)
        {
            $key = strtolower(trim($favorite_costume->costume));

            $legacy_costume = $legacy_costumes[$key];

            $is_multiple_club = array_key_exists($favorite_costume->club, $this->dual_club_map);

            $costume_id = $legacy_costume['id'];

            if ($is_multiple_club)
            {
                //  expand club ids from dual ids and flatten the array to
                //  get all individual club ids for this costume
                $club_ids = $this->expandDualClubIds($legacy_costume['clubs']);

                foreach ($club_ids as $club_id)
                {
                    $this->migrateTrooperCostume($club_map, $costume_id, $club_id, $favorite_costume);
                }
            }
            else
            {
                $this->migrateTrooperCostume($club_map, $costume_id, $favorite_costume->club, $favorite_costume);
            }
        }
    }

    private function migrateEventTrooperCostumes($club_map, $legacy_costumes): void
    {
        $event_costumes = DB::table('event_sign_up')
            ->join('costumes', 'costumes.id', '=', 'event_sign_up.costume')
            ->join('troopers', 'troopers.id', '=', 'event_sign_up.trooperid')
            ->whereRaw('costumes.club IS NOT NULL AND costumes.club != 4')
            ->whereRaw("costumes.costume not in ('NA', 'N/A', 'Handler', 'Command Staff')")
            ->selectRaw('distinct costumes.costume, costumes.club, costumes.id, event_sign_up.trooperid')
            ->get();

        foreach ($event_costumes as $event_costume)
        {
            $key = strtolower(trim($event_costume->costume));

            $legacy_costume = $legacy_costumes[$key];

            $is_multiple_club = array_key_exists($event_costume->club, $this->dual_club_map);

            $costume_id = $legacy_costume['id'];

            if ($is_multiple_club)
            {
                //  expand club ids from dual ids and flatten the array to
                //  get all individual club ids for this costume
                $club_ids = $this->expandDualClubIds($legacy_costume['clubs']);

                foreach ($club_ids as $club_id)
                {
                    $this->migrateTrooperCostume($club_map, $costume_id, $club_id, $event_costume);
                }
            }
            else
            {
                $this->migrateTrooperCostume($club_map, $costume_id, $event_costume->club, $event_costume);
            }
        }
    }

    private function migrateTrooperCostume($club_map, $costume_id, $club_id, $legacy_costume): void
    {
        $club = $club_map->firstWhere('costume_club_id', $club_id) ?? null;

        $org_costume = OrganizationCostume::query()
            ->where(OrganizationCostume::ORGANIZATION_ID, $club['id'])
            ->where(OrganizationCostume::COSTUME_ID, $costume_id)
            ->first();

        $trooper_costume = TrooperCostume::query()
            ->where(TrooperCostume::TROOPER_ID, $legacy_costume->trooperid)
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume->id)
            ->first();

        if ($trooper_costume === null)
        {
            $trooper_costume = new TrooperCostume();

            $trooper_costume->trooper_id = $legacy_costume->trooperid;
            $trooper_costume->organization_costume_id = $org_costume->id;

            $trooper_costume->save();
        }
    }
}