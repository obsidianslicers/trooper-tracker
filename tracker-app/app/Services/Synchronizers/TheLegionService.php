<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use stdClass;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


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
            list($codex, $costume_name) = explode(" - ", $link->textContent, 2);

            $costume_name = trim($costume_name);

            $org_costume = $this->organization->organization_costumes()
                ->where(OrganizationCostume::NAME, $costume_name)
                ->first();

            if ($org_costume === null)
            {
                $org_costume = new OrganizationCostume();
                $org_costume->organization_id = $this->organization->id;
                $org_costume->name = $costume_name;
            }

            $org_costume->verified_at = now();

            $org_costume->save();
        }
        
        // Additionally, fetch per-trooper costumes via the 501st member API
        // and populate tt_trooper_costumes with per-member images and prefixes.
        $troopers = $this->organization->troopers()->get();

        foreach ($troopers as $trooper)
        {
            $legion_id = $trooper->pivot->identifier ?? null;
            if (empty($legion_id))
            {
                continue;
            }

            $url = "https://www.501st.com/memberAPI/v3/legionId/{$legion_id}/costumes";

            try
            {
                $json = @file_get_contents($url);
            } catch (Exception $e) {
                Log::error('TheLegionService: error fetching costumes for legionId ' . $legion_id . ' - ' . $e->getMessage());
                continue;
            }

            if (empty($json))
                {
                continue;
            }

            $data = json_decode($json, true);
            if (! is_array($data) || empty($data['costumes']))
            {
                continue;
            }

            foreach ($data['costumes'] as $c)
            {
                $costume_name = $c['costumeName'] ?? null;
                if (empty($costume_name))
                {
                    continue;
                }

                // Ensure OrganizationCostume exists
                $org_costume = $this->organization->organization_costumes()
                    ->where('name', $costume_name)
                    ->first();

                if ($org_costume === null)
                {
                    $org_costume = new OrganizationCostume();
                    $org_costume->organization_id = $this->organization->id;
                    $org_costume->name = $costume_name;
                }

                if (Schema::hasColumn('tt_organization_costumes', 'verified_at'))
                {
                    $org_costume->verified_at = now();
                }

                $org_costume->save();

                // Create or update TrooperCostume (link trooper -> organization costume)
                try
                {
                    // find or create org costume (do not set verified_at when creating)
                    $org_costume = $this->organization->organization_costumes()
                        ->where('name', $costume_name)
                        ->first();

                    if ($org_costume === null)
                    {
                        $org_costume = new OrganizationCostume();
                        $org_costume->organization_id = $this->organization->id;
                        $org_costume->name = $costume_name;
                        $org_costume->verified_at = null;
                        $org_costume->save();
                    }

                    // Now ensure trooper_costume exists
                    $trooper_costume = \App\Models\TrooperCostume::where('trooper_id', $trooper->id)
                        ->where('costume_id', $org_costume->id)
                        ->first();

                    $tc_data = [
                        'trooper_id' => $trooper->id,
                        'costume_id' => $org_costume->id,
                        'costume_prefix' => $c['prefix'] ?? null,
                        'small_image_url' => $c['thumbnail'] ?? null,
                        'large_image_url' => $c['photoURL'] ?? ($c['photo'] ?? null),
                        'bucket_off_url' => $c['bucketOffPhoto'] ?? null,
                    ];

                    if ($trooper_costume === null)
                    {
                        \App\Models\TrooperCostume::create($tc_data);
                    } else {
                        $changed = false;
                        foreach (['costume_prefix','small_image_url','large_image_url','bucket_off_url'] as $k) {
                            if (($trooper_costume->{$k} ?? null) !== ($tc_data[$k] ?? null))
                            {
                                $trooper_costume->{$k} = $tc_data[$k] ?? null;
                                $changed = true;
                            }
                        }
                        if ($changed) { $trooper_costume->save(); }
                    }
                } catch (Exception $e) {
                    Log::error('TheLegionService: failed to create/update TrooperCostume for legionId ' . $legion_id . ' - ' . $e->getMessage());
                }
            }
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

        $member_error = $member->error ?? null;
        $member_approved = $member->memberApproved ?? null;
        $member_standing = $member->memberStanding ?? null;
        $member_status = $member->memberStatus ?? null;

        if (isset($member_error))
        {
            if ($pivot->membership_status == MembershipStatus::ACTIVE)
            {
                $pivot->verified_at = now();
                $pivot->membership_status = MembershipStatus::NONE;
                $pivot->save();
            }
        }
        else
        {
            $status = $pivot->status;

            if ($member_approved == 'YES' && $member_standing == 'Good')
            {
                switch ($member_status)
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

    public static function parseRequestAppearance(string $message): Event
    {
        $parsed = static::parseMessage($message);

        return new Event([
            Event::CONTACT_NAME => $parsed['Contact Name'] ?? null,
            Event::CONTACT_PHONE => $parsed['Contact Phone Number'] ?? null,
            Event::CONTACT_EMAIL => $parsed['Contact Email'] ?? null,
            Event::NAME => $parsed['Event Name'] ?? null,
            Event::VENUE => $parsed['Venue'] ?? null,
            Event::VENUE_ADDRESS => $parsed['Venue address'] ?? null,
            Event::EVENT_START => isset($parsed['Event Start']) ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event Start']) : null,
            Event::EVENT_END => isset($parsed['Event End']) ? Carbon::createFromFormat('m/d/Y - Hi', $parsed['Event End']) : null,
            Event::EVENT_WEBSITE => $parsed['Event Website'] ?? null,
            Event::EXPECTED_ATTENDEES => $parsed['Expected number of attendees'] ?? null,
            Event::REQUESTED_NUMBER_CHARACTERS => $parsed['Requested number of characters'] ?? null,
            Event::REQUESTED_CHARACTER_TYPES => $parsed['Requested character types'] ?? null,
            Event::SECURE_STAGING_AREA => ($parsed['Secure changing/staging area'] ?? '') === 'Yes',
            Event::ALLOW_BLASTERS => ($parsed['Can troopers carry blasters'] ?? '') === 'Yes',
            Event::ALLOW_PROPS => ($parsed['Can troopers carry/bring props like lightsabers and staffs'] ?? '') === 'Yes',
            Event::PARKING_AVAILABLE => ($parsed['Is parking available'] ?? '') === 'Yes',
            Event::ACCESSIBLE => ($parsed['Is venue accessible to those with limited mobility'] ?? '') === 'Yes',
            Event::AMENITIES => $parsed['Amenities available at venue'] ?? null,
            Event::COMMENTS => $parsed['Comments'] ?? null,
            Event::REFERRED_BY => $parsed['Referred by'] ?? null,
            Event::SOURCE => $message,
        ]);
    }

}
