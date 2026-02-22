<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait HasCostumeMaps
{
    protected function getMappedLegacyCostumes(): Collection
    {
        return DB::table('costumes')
            ->whereNotNull('club')
            ->where('club', '!=', 4)
            ->orderBy('id')
            ->get()
            // Collapses "Jawa" (501) and "Jawa" (RL) into one key
            // Group by a lowercased, trimmed version of the costume name
            ->groupBy(fn($item) => strtolower(trim($item->costume)))
            ->map(function ($group)
            {
                // $group is a collection of all rows with the same name
                return [
                    'clubs' => $group->pluck('club')->toArray(),
                    'id' => $group->first()->id, // Take the ID of the first record for this costume name
                    'ids' => $group->pluck('id')->toArray(), // Get all IDs for this costume name
                    'costume' => $group->first()->costume, // The costume name is the same for all records in the group
                ];
            });
    }
}