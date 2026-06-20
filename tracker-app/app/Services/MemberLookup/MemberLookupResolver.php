<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;

class MemberLookupResolver
{
    public function resolve(Organization $organization): ?MemberLookupInterface
    {
        if (!$organization->service_class || !class_exists($organization->service_class))
        {
            return null;
        }

        $service = app($organization->service_class, ['organization' => $organization]);

        return $service->lookupMember();
    }
}
