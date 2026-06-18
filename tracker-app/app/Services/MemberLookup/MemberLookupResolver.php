<?php

declare(strict_types=1);

namespace App\Services\MemberLookup;

use App\Contracts\MemberLookupInterface;
use App\Models\Organization;
use App\Services\GoogleService;
use App\Services\Synchronizers\DroidBuildersService;
use App\Services\Synchronizers\MandalorianMercsService;
use App\Services\Synchronizers\RebelLegionService;
use App\Services\Synchronizers\SaberGuildServices;
use App\Services\Synchronizers\TheLegionService;

class MemberLookupResolver
{
    public function __construct(private readonly GoogleService $google) {}

    public function resolve(Organization $organization): ?MemberLookupInterface
    {
        return match($organization->service_class) {
            TheLegionService::class        => new TheLegionMemberLookupService(),
            RebelLegionService::class      => new GoogleSheetsMemberLookupService($organization, $this->google, 'Troopers', 'ID', 'Name'),
            SaberGuildServices::class      => new GoogleSheetsMemberLookupService($organization, $this->google, 'Troopers', 'ID', 'IRL Name', 'SG-'),
            DroidBuildersService::class    => new GoogleSheetsMemberLookupService($organization, $this->google, 'Troopers', 'Forum Name'),
            MandalorianMercsService::class => new GoogleSheetsMemberLookupService($organization, $this->google, 'Troopers', 'ID', 'Name'),
            default                        => null,
        };
    }
}
