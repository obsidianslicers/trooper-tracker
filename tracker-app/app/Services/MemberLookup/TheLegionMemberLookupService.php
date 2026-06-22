<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TheLegionMemberLookupService implements MemberLookupInterface
{
    public function lookup(string $identifier): ?array
    {
        $cache_key = "tracker:member-lookup:legion:{$identifier}";

        $result = Cache::remember($cache_key, 3600, function () use ($identifier) {
            $response = Http::timeout(5)->get("https://api.501st.com/legionId/{$identifier}");

            if (!$response->successful())
            {
                return false;
            }

            $json = $response->json();

            if (empty($json) || isset($json['error']))
            {
                return false;
            }

            return [
                'identifier' => (string) ($json['legionId'] ?? $identifier),
                'formatted_identifier' => $json['formattedLegionId'] ?? $identifier,
                'full_name' => $json['fullName'] ?? null,
                'status' => $json['memberStatus'] ?? null,
                'standing' => $json['memberStanding'] ?? null,
                'is_approved' => ($json['memberApproved'] ?? null) === 'YES',
                'unit_name' => $json['garrisonName'] ?? null,
                'profile_url' => $json['profileUrl'] ?? null,
                'thumbnail_url' => $json['primaryThumbnail'] ?? null,
            ];
        });

        return $result === false ? null : $result;
    }
}
