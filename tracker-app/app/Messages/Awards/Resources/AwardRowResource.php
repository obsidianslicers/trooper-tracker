<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff awards list.
 *
 * @mixin Award
 */
class AwardRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Award::ID => $this->id,
            Award::NAME => $this->name,
            'organization' => $this->organization === null ? null : [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ],
            'logo_url' => map_image_url(
                $this->organization->image_path_sm ?? '',
                'img/icons/organization-32x32.png'
            ),
            'troopers_count' => format_number((int) $this->troopers_count),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
        ];
    }
}
