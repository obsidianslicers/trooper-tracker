<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use Illuminate\Support\Facades\Log;


/**
 * Service class for managing Mandalorian Mercs organization data.
 *
 * This service interacts with Google Sheets to synchronize member information,
 * update trooper statuses, and manage organization-specific data.
 */
class SaberGuildServices extends BaseOrganizationService
{
    public function syncCostumes(): void
    {
        $costume_rows = $this->getSheetRows();

        foreach ($costume_rows as $row)
        {
            $name = $this->cleanInput($row[0] ?? null);
            $rank_title = $this->cleanInput($row[1] ?? null);
            $forum_id = $this->cleanInput($row[2] ?? null);
            $costume_name = $this->cleanInput($row[3] ?? null);
            $costume_image = $this->cleanInput($row[4] ?? null);

            if (empty($costume_name))
            {
                continue;
            }

            // Map to organization costume and trooper costume
            $identifier = $forum_id . '';

            if (empty($identifier))
            {
                Log::warning(__CLASS__ . " skipping trooper costume '{$costume_name}' with empty identifier for org {$this->organization->id}");

                continue;
            }

            // Convert Google Drive link
            if (strpos($costume_image, "view?usp=drivesdk") !== false)
            {
                $segments = explode("/", $costume_image);
                $costume_image = "https://drive.google.com/uc?id=" . $segments[5] . "";
            }

            // Ensure organization costume exists 
            $org_costume = $this->getOrganizationCostume($costume_name);

            $trooper = $this->getTrooper($identifier);

            if ($trooper === null)
            {
                Log::warning(__CLASS__ . " no trooper found for identifier '{$identifier}' for org {$this->organization->id}; skipping costume '{$costume_name}'");

                continue;
            }

            $this->syncTrooperCostume($trooper, $org_costume, $costume_image);
        }
    }

    //     /*<?php

    // * This file is used for scraping Saber Guild data.
// * 
// * This should be run weekly by a cronjob.
// *
// * @author  Matthew Drennan
// *
// *

    // // Include config
// include(dirname(__DIR__) . '/../../config.php');

    // // Get Simple PHP DOM Tool - just a note, for this code to work, $stripRN must be false in tool
// include(dirname(__DIR__) . '/../../tool/dom/simple_html_dom.php');

    // // Purge SG troopers
// $statement = $conn->prepare("DELETE FROM sg_troopers");
// $statement->execute();

    // // Pull extra data from spreadsheet
// $values = getSheet("1PcveycMujakkKeG2m4y8iFunrFbo2KVpQJ00GyPI3b8", "Sheet1");


    // // Set up count
// $i = 0;

    // foreach($values as $value)
// {
// // If not first
// if($i != 0)
// {
//     // Set up image

    //     $value[2] = cleanInput($value[2]);
//     $value[0] = cleanInput($value[0]);
//     $value[1] = cleanInput($value[1]);
//     $value[3] = cleanInput($value[3]);
//     $image = cleanInput($image);

    //     // Insert into database
//     $statement = $conn->prepare("INSERT INTO sg_troopers (
//     $statement->execute();


    // }

    // // Increment
// $i++;
// }

    // 
    public function syncAllMembers(): void
    {
    }

    public function syncMember(string $identifier): void
    {
    }
}
