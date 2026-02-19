<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class TheLegionService extends BaseOrganizationService
{
    protected function synchronize(): void
    {
        $this->syncCostumes();
        $this->syncTroopers();
    }

    private function syncCostumes(): void
    {
        $html = Http::get('https://crls.501st.com/costume-reference-library/costumes-by-name')->body();

        $crawler = new Crawler($html);
        $links = $crawler->filter('article a');

        foreach ($links as $link)
        {
            $text = trim($link->textContent);

            // AR - ARC Trooper (CW) (Phase 1): Fordo
            [$prefix, $costume_name] = explode(' - ', $text, 2);

            $costume_name = trim($costume_name);

            $this->getOrCreateOrganizationCostume($costume_name, $prefix);
        }
    }

    private function syncTroopers(): void
    {
        // Additionally, fetch per-trooper costumes via the 501st member API
        // and populate tt_trooper_costumes with per-member images and prefixes.
        $troopers = $this->organization->troopers()->get();

        foreach ($troopers as $trooper)
        {
            $legion_id = $trooper->pivot->identifier;

            $url = "https://www.501st.com/memberAPI/v3/legionId/{$legion_id}/costumes";

            $json = Http::get($url)->json();

            if (!is_array($json) || empty($json['costumes']))
            {
                continue;
            }

            $trooper = $this->getTrooper($json['legionId'] . '');

            if ($trooper === null)
            {
                continue;
            }

            $status = $this->convertStatus($trooper, $json);

            $this->syncTrooperStatus($trooper, $status);

            foreach ($json['costumes'] as $c)
            {
                $costume_name = $c['costumeName'] ?? null;

                if (empty($costume_name))
                {
                    continue;
                }

                //  should be set via syncCostumes
                $org_costume = $this->getOrCreateOrganizationCostume($costume_name);

                $attributes = [
                    TrooperCostume::IMAGE_URL_LG => $c['photoURL'] ?? ($c['photo'] ?? null),
                    TrooperCostume::IMAGE_URL_SM => $c['thumbnail'] ?? null,
                    TrooperCostume::IMAGE_URL_BUCKET_OFF => $c['bucketOffPhoto'] ?? null,
                ];

                $this->syncTrooperCostume($trooper, $org_costume, $attributes);
            }
        }
    }

    private function convertStatus(Trooper $trooper, $json): MembershipStatus
    {
        $pivot = $trooper->pivot;

        $member_error = $json['error'] ?? null;
        $member_approved = $json['memberApproved'] ?? null;
        $member_standing = $json['memberStanding'] ?? null;
        $member_status = $json['memberStatus'] ?? null;

        if (isset($member_error))
        {
            if ($pivot->membership_status == MembershipStatus::ACTIVE)
            {
                return MembershipStatus::NONE;
            }
        }
        else
        {
            if ($member_approved == 'YES' && $member_standing == 'Good')
            {
                switch ($member_status)
                {
                    case 'Active':
                        return MembershipStatus::ACTIVE;
                    case 'Reserve':
                        return MembershipStatus::RESERVE;
                }
            }

            return MembershipStatus::NONE;
        }

        return $pivot->membership_status;
    }
}
