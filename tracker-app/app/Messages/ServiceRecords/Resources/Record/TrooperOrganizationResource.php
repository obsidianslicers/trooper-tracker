<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club the trooper belongs to, with their ID there and the unit they're assigned to, on the
 * service record's Costumes tab.
 *
 * @property Organization $resource
 */
class TrooperOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organization = $this->resource;

        return [
            Organization::ID => $organization->id,
            Organization::NAME => $organization->name,
            'logo_url' => map_image_url($organization->image_path_sm ?? '', 'img/icons/organization-32x32.png'),
            'identifier_label' => $organization->identifier_display,
            'identifier' => $organization->pivot->identifier,
            'assignment' => $organization->assignment->organization->name ?? null,
        ];
    }
}
