<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;
use App\Plugins\PluginClubSynchronizers;

class MemberLookupResolver
{
    public function __construct(private readonly PluginClubSynchronizers $synchronizers) {}

    public function resolve(Organization $organization): ?MemberLookupInterface
    {
        return $this->synchronizers->memberLookup($organization);
    }
}
