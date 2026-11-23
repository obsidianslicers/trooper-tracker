<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Enums\OrganizationType;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The organization on the Command Staff organization update page: its name, roster sync, the
 * organization above it, and its logo. Load `parent` first.
 *
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parent = $this->parent;

        return [
            Organization::ID => $this->id,
            Organization::NAME => $this->name,
            Organization::SYNCHRONIZER => $this->synchronizer,
            'is_club' => $this->type === OrganizationType::ORGANIZATION,
            'parent' => $parent === null ? null : [
                Organization::NAME => $parent->name,
                'label' => __('admin.Parent :type', ['type' => $parent->type->label()]),
            ],
            'logo_url' => $this->logoUrl(),
            TrooperStampsResource::TROOPER_STAMPS => new TrooperStampsResource($this->resource),
        ];
    }

    /**
     * Versioned by the last update, so a replaced logo isn't served from the browser's cache.
     */
    private function logoUrl(): string
    {
        $url = map_image_url($this->image_path_lg, DEFAULT_ORGANIZATION_IMAGE_URL);

        return $this->image_path_lg === null
            ? $url
            : $url.'?v='.$this->updated_at?->timestamp;
    }
}
