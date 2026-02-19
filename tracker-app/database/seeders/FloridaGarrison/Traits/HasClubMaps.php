<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Traits;

use App\Models\Organization;
use Exception;

trait HasClubMaps
{
    protected array $dual_club_map = [
        5 => [0, 1],
        7 => [0, 2],
        8 => [1, 2],
        9 => [0, 6],
        10 => [1, 6],
        11 => [0, 1, 6],
        12 => [0, 1, 2],
    ];

    /*
        5  => 'Dual (501st + Rebel)',
        7  => 'Dual-Mando (501st + Mando)',
        8  => 'Dual-Mando (Rebel + Mando)',
        9  => 'Dual-Saber (501st + Saber Guild)',
        10 => 'Dual-Saber (Rebel + Saber Guild)',
        11 => 'Triple-Dual (501st + Rebel + Saber Guild)',
        12 => 'Triple-Dual (501st + Rebel + Mando)',
        */

    protected function expandDualClubIds(array $club_ids): array
    {
        $clubs = [];

        foreach ($club_ids as $id)
        {
            if (array_key_exists($id, $this->dual_club_map))
            {
                $clubs = array_merge($clubs, $this->dual_club_map[$id]);
            }
            else
            {
                $clubs[] = $id;
            }
        }

        return array_unique($clubs);
    }

    protected function getCostumeClubMap(): array
    {
        // Hardcoded costumeClubID → organization name
        $legacy_clubs = [
            0 => ['name' => '501st Legion', 'column' => 'p501', 'identity' => 'tkid', 'legacy_id' => 0],
            1 => ['name' => 'Rebel Legion', 'column' => 'pRebel', 'identity' => 'rebelforum', 'legacy_id' => 6],
            2 => ['name' => 'Mandalorian Mercs', 'column' => 'pMando', 'identity' => 'mandoid', 'legacy_id' => 8],
            3 => ['name' => 'Droid Builders', 'column' => 'pDroid', 'identity' => 'forum_id', 'legacy_id' => 7],
            //4 => ['name' => 'Other', 'column' => 'pOther', 'identity' => ''],
            6 => ['name' => 'Saber Guild', 'column' => 'pSG', 'identity' => 'sgid', 'legacy_id' => 10],
            13 => ['name' => 'Dark Empire', 'column' => 'pDE', 'identity' => 'de_id', 'legacy_id' => 13],
        ];

        // Build final map: costumeClubID → organization_id
        $map = [];

        foreach ($legacy_clubs as $costume_club_id => $meta)
        {
            $organization = Organization::firstWhere(Organization::NAME, $meta['name']);

            if ($organization)
            {
                $map[$meta['column']] = [
                    'id' => $organization->id,
                    'costume_club_id' => $costume_club_id,
                    'identity' => $meta['identity'],
                ];
            }
            else
            {
                throw new Exception("Organization not found for name: {$meta['name']}");
            }
        }

        return $map;
    }

    protected function getOrganizationClubMap(): array
    {
        // Hardcoded squadID → club name
        $legacy_clubs = [
            0 => ['name' => '501st Legion', 'column' => 'p501', 'identity' => 'tkid'],
            6 => ['name' => 'Rebel Legion', 'column' => 'pRebel', 'identity' => 'rebelforum'],
            7 => ['name' => 'Droid Builders', 'column' => 'pDroid', 'identity' => ''],
            8 => ['name' => 'Mandalorian Mercs', 'column' => 'pMando', 'identity' => 'mandoid'],
            //9 => ['name' => 'Other', 'column' => 'pOther', 'identity' => ''],
            10 => ['name' => 'Saber Guild', 'column' => 'pSG', 'identity' => 'sgid'],
            13 => ['name' => 'Dark Empire', 'column' => 'pDE', 'identity' => 'de_id'],
        ];

        // Build final map: squadID → club_id
        $map = [];

        foreach ($legacy_clubs as $legacy_id => $meta)
        {
            $organization = Organization::firstWhere(Organization::NAME, $meta['name']);

            if ($organization)
            {
                $map[$meta['column']] = [
                    'id' => $organization->id,
                    'legacy_id' => $legacy_id,
                    'identity' => $meta['identity'],
                ];
            }
            else
            {
                throw new Exception("Organization not found for name: {$meta['name']}");
            }
        }

        return $map;
    }
}