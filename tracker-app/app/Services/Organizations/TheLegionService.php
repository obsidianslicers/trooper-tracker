<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\MembershipStatus;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use DOMDocument;
use DOMXPath;
use Exception;
use stdClass;

class TheLegionService extends BaseOrganizationService
{
    public function syncCostumes(): void
    {
        // Load an entire HTML file
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTMLFile('https://crls.501st.com/costume-reference-library/costumes-by-name');

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $links = $xpath->query('//article//a');

        foreach ($links as $link)
        {
            //AR - ARC Trooper (CW) (Phase 1): Fordo
            list($codex, $name) = explode(" - ", $link->textContent, 2);

            $name = trim($name);

            $costume = $this->organization->organization_costumes()
                ->where(OrganizationCostume::NAME, $name)
                ->first();

            if ($costume == null)
            {
                $costume = new OrganizationCostume();
                $costume->organization_id = $this->organization->id;
                $costume->name = $name;
            }

            $costume->verified_at = now();

            $costume->save();
        }
    }

    public function syncAllMembers(): void
    {
        $troopers = $this->organization->troopers()
            ->wherePivotNull(TrooperOrganization::VERIFIED_AT)
            ->get();

        foreach ($troopers as $trooper)
        {
            $id = $trooper->pivot->identifier;

            $member = $this->getLegionMember($id);

            $this->updateTrooperStatus($trooper, $member);
        }


        // $json = file_get_contents("https://www.501st.com/memberAPI/v3/garrisons");

        // $garrisons = json_decode($json, false);

        // foreach ($this->organization->organizations as $region)
        // {
        //     $garrison = $this->findGarrison($garrisons, $region->name);

        //     $url = "https://www.501st.com/memberAPI/v3/garrisons/{$garrison->id}/members";

        //     $json = file_get_contents($url);

        //     //  stomp on this with the actual garrison results
        //     $garrison = json_decode($json, false);

        //     foreach ($garrison->unit->members as $member)
        //     {
        //         $this->syncMember((string) $member->legionId);
        //     }
        // }
    }

    public function syncMember(string $identifier): void
    {
        $trooper = $this->organization->troopers()
            ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
            ->first();

        $member = $this->getLegionMember($trooper->pivot->identifier);

        $this->updateTrooperStatus($trooper, $member);
    }

    private function getLegionMember($legion_id)
    {
        $url = "https://www.501st.com/memberAPI/v3/legionId/{$legion_id}";

        $json = file_get_contents($url);

        return json_decode($json, false);
    }

    private function updateTrooperStatus(Trooper $trooper, $member): void
    {
        $pivot = $trooper->pivot;

        if (isset($member->error))
        {
            if ($pivot->membership_status == MembershipStatus::ACTIVE)
            {
                $pivot->verified_at = now();
                $pivot->membership_status = MembershipStatus::NOT_FOUND;
                $pivot->save();
            }
        }
        else
        {
            $status = $pivot->status;

            if ($member->memberApproved == 'YES' && $member->memberStanding == 'Good')
            {
                switch ($member->memberStatus)
                {
                    case 'Active':
                        $status = MembershipStatus::ACTIVE;
                        break;
                    case 'Reserve':
                        $status = MembershipStatus::RESERVE;
                        break;
                    default:
                        $status = MembershipStatus::NONE;
                        break;
                }
            }
            else
            {
                $status = MembershipStatus::NONE;
            }

            $pivot->verified_at = now();
            $pivot->membership_status = $status;
            $pivot->save();
        }
    }

    private function findGarrison(stdClass $garrisons, string $name): stdClass
    {
        foreach ($garrisons->garrisons as $garrison)
        {
            if ($garrison->name == $name)
            {
                return $garrison;
            }
        }

        $msg = "Could not find Garrison={$name}";

        throw new Exception($msg);
    }
    // // Fetch trooper data

    // if (!$trooperData || empty($trooperData['unit']['members'])) {
//     die("Failed to retrieve trooper data.");
// }

    // // Prepare database insertion queries
// $trooperStmt = $conn->prepare("INSERT INTO 501st_troopers (legionid, name, thumbnail, link, squad, approved, status, standing, joindate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
// $costumeStmt = $conn->prepare("INSERT INTO 501st_costumes (legionid, costumeid, prefix, costumename, photo, thumbnail, bucketoff) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // // Process members
// foreach ($trooperData['unit']['members'] as $trooper) {
//     $legionId = $trooper['legionId'];

    //     // Fetch detailed member data
//     $json2 = file_get_contents("https://www.501st.com/memberAPI/v3/legionId/$legionId");
//     $member = json_decode($json2, true);

    //     if (!$member) continue;

    //     $trooperStmt->bind_param(
//         "ssssiiiss",
//         $legionId,
//         $trooper['fullName'],
//         $trooper['thumbnail'],
//         $trooper['link'],
//         convertSquadId($trooper['squadId']),
//         convertMemberApproved($member['memberApproved']),
//         convertMemberStatus($member['memberStatus']),
//         convertMemberStanding($member['memberStanding']),
//         $member['joinDate']
//     );
//     $trooperStmt->execute();

    //     // Fetch and insert costume data
//     $json3 = file_get_contents("https://www.501st.com/memberAPI/v3/legionId/$legionId/costumes");
//     $costumeData = json_decode($json3, true);

    //     if ($costumeData && !empty($costumeData['costumes'])) {
//         foreach ($costumeData['costumes'] as $costume) {
//             $costumeStmt->bind_param(
//                 "sssssss",
//                 $legionId,
//                 $costume['costumeId'],
//                 $costume['prefix'],
//                 $costume['costumeName'],
//                 $costume['photoURL'],
//                 $costume['thumbnail'],
//                 $costume['bucketOffPhoto']
//             );
//             $costumeStmt->execute();
//         }
//     }
// }


    // /**
//  * Returns the squad's ID for troop tracker
//  *
//  * @param int $value The string value to be formatted
//  * @return int Returns squad ID based on value
//  *
// function convertSquadId($value) {
//     $squads = [
//         110 => 5,  // Tampa Bay Squad
//         136 => 4,  // Squad 7
//         126 => 3,  // Parjai Squad
//         124 => 2,  // Makaze Squad
//         113 => 1   // Everglades Squad
//     ];
//     return $squads[$value] ?? 0;
// }

}
