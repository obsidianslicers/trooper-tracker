<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\Costume;
use Database\Seeders\FloridaGarrison\Traits\HasCostumeMaps;
use Illuminate\Database\Seeder;

class CostumeSeeder extends Seeder
{
    use HasCostumeMaps;

    /**
     * Consolidating multiple costume records from the legacy 'costumes' table into the new 'costumes' table.
     * Dual entries for multiple clubs will be resolved by taking the first record (lowest ID) for 
     * each unique costume name.
     */
    public function run(): void
    {
        $legacy_costumes = $this->getMappedLegacyCostumes();

        foreach ($legacy_costumes as $costume_name => $legacy_costume)
        {
            if (empty($costume_name))
            {
                continue; // Skip records with empty costume names
            }

            $real_name = $legacy_costume['costume'];

            $costume = Costume::query()
                ->where(Costume::NAME, $real_name)
                ->first();

            if ($costume === null)
            {
                //  first ID wins on Dual records
                $costume = new Costume(['id' => $legacy_costume['id']]);
                $costume->name = $real_name;
                $costume->save();
            }
        }
    }
}