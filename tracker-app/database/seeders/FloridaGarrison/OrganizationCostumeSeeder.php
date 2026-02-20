<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\Costume;
use App\Models\OrganizationCostume;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Database\Seeders\FloridaGarrison\Traits\HasCostumeMaps;
use Illuminate\Database\Seeder;

class OrganizationCostumeSeeder extends Seeder
{
    use HasCostumeMaps;
    use HasClubMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $club_map = collect($this->getCostumeClubMap());

        $legacy_costumes = $this->getMappedLegacyCostumes();

        foreach ($legacy_costumes as $costume_name => $legacy_costume)
        {
            // This is now an array of club IDs for this costume name
            $costume_id = $legacy_costume['id'];
            $club_ids = $legacy_costume['clubs'];

            $real_name = $legacy_costume['costume'];

            $costume = Costume::query()
                ->where(Costume::NAME, $real_name)
                ->first();

            if ($costume === null)
            {
                continue;
            }

            //  expand club ids from dual ids and flatten the array to
            //  get all individual club ids for this costume
            $club_ids = $this->expandDualClubIds($club_ids);

            foreach ($club_ids as $club_id)
            {
                $club = $club_map->firstWhere('costume_club_id', $club_id) ?? null;

                $this->createOrganizationCostume($costume->id, $club['id']);
            }
        }
    }

    private function createOrganizationCostume(int $costume_id, int $organization_id): void
    {
        $org_costume = OrganizationCostume::query()
            ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
            ->where(OrganizationCostume::COSTUME_ID, $costume_id)
            ->first();

        if ($org_costume === null)
        {
            $org_costume = new OrganizationCostume();
            $org_costume->costume_id = $costume_id;
            $org_costume->organization_id = $organization_id;
            $org_costume->save();
        }
    }
}