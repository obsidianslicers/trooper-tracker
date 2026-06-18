<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;
use App\Services\Synchronizers\TheLegionService;

class MemberLookupResolver
{
    private array $map = [
        TheLegionService::class => TheLegionMemberLookupService::class,
    ];

    public function resolve(Organization $organization): ?MemberLookupInterface
    {
        $service_class = $organization->service_class;

        if (empty($service_class) || !isset($this->map[$service_class]))
        {
            return null;
        }

        $lookup_class = $this->map[$service_class];

        return new $lookup_class();
    }
}
