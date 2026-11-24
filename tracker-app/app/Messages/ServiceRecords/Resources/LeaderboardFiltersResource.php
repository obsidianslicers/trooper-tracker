<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The leaderboards' filters (service-records/Leaderboard and Costume): the period, the
 * club picked and the clubs to pick from.
 *
 * @property Collection<int, Organization> $resource The clubs to pick from
 */
class LeaderboardFiltersResource extends JsonResource
{
    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  array<int, int>  $day_options
     */
    public function __construct(
        Collection $organizations,
        private readonly ?int $days,
        private readonly array $day_options,
        private readonly ?Organization $organization,
    ) {
        parent::__construct($organizations);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'days' => $this->days,
            'day_options' => $this->day_options,
            'organization_id' => $this->organization?->id,
            'organization' => $this->organization ? [
                Organization::NAME => $this->organization->name,
                'logo_url' => map_image_url(
                    $this->organization->image_path_sm,
                    'img/icons/organization-32x32.png'
                ),
            ] : null,
            'organizations' => $this->resource
                ->map(fn (Organization $organization): array => [
                    'value' => $organization->id,
                    'label' => $organization->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
