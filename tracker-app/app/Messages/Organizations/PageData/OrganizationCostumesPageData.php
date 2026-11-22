<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Hyperdrive\Message;
use Illuminate\Support\Carbon;

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
            ->get()
            ->map(fn (Costume $costume) => [
                'id' => $costume->id,
                'name' => $costume->name,
                'synchronized_at' => $costume->pivot->synchronized_at
                    ? Carbon::parse($costume->pivot->synchronized_at)->localFormat('M j, Y')
                    : null,
            ]);

        return [
            'organization' => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
                'is_club' => true,
            ],
            'costumes' => $costumes,
        ];
    }
}
