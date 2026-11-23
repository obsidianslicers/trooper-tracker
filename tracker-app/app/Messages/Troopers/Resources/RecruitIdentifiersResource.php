<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * For the Recruit Trooper form (admin/troopers/Recruit), keyed by the organizations the recruiter
 * may add troopers to: what the identifier field is called there, and whether it is required.
 * A region or unit takes its club's setting when it has none of its own.
 *
 * @property Collection<int, Organization> $resource
 */
class RecruitIdentifiersResource extends JsonResource
{
    /** Keyed by organization id: without this, resolving would renumber the keys. */
    public bool $preserveKeys = true;

    /**
     * @param  Collection<int, Organization>  $organizations
     * @param  Collection<int, Organization>  $clubs  keyed by id
     */
    public function __construct(Collection $organizations, private readonly Collection $clubs)
    {
        parent::__construct($organizations);
    }

    /**
     * @return array<int, array{label: string, is_required: bool}>
     */
    public function toArray(Request $request): array
    {
        return $this->resource->mapWithKeys(function (Organization $organization): array
        {
            $club = $this->clubs[self::clubId($organization)] ?? null;
            $display = $organization->identifier_display ?? $club?->identifier_display;
            $validation = $organization->identifier_validation ?? $club?->identifier_validation;

            return [$organization->id => [
                'label' => $display ?: __('admin.Member ID'),
                'is_required' => in_array('required', explode('|', (string) $validation), true),
            ]];
        })->all();
    }

    public static function clubId(Organization $organization): int
    {
        $parts = array_filter(explode(Organization::NODE_PATH_SEP, $organization->node_path));

        return (int) reset($parts);
    }
}
