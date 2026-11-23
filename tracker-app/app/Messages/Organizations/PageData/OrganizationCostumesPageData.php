<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Messages\Organizations\Resources\ClubCostumeResource;
use App\Messages\Organizations\Resources\ClubReferenceResource;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Hyperdrive\Message;

/**
 * The costumes a club offers, with when its roster sync last saw each.
 *
 * @method static array call(Organization $organization)
 */
final class OrganizationCostumesPageData extends Message
{
    public function __construct(private readonly Organization $organization) {}

    public function handle(): array
    {
        $costumes = $this->organization->costumes()
            ->wherePivotNull(OrganizationCostume::DELETED_AT)
            ->orderBy(Costume::NAME)
            ->get();

        return [
            'organization' => new ClubReferenceResource($this->organization),
            'costumes' => ClubCostumeResource::collection($costumes),
        ];
    }
}
