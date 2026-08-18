<?php

namespace App\Messages\Account\Resources;

use App\Models\TrooperAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperMembershipCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(TrooperAssignment $trooper_assignment) => [
                'membership_path' => $trooper_assignment->membership_path,
                'identifier' => $trooper_assignment->organization_membership->identifier,
                'membership_status' => $trooper_assignment->organization_membership->membership_status,
                'image_url' => map_image_url(
                    path: $trooper_assignment->organization_membership->organization?->image_path_sm,
                    default: DEFAULT_ORGANIZATION_IMAGE_URL
                ),
            ])->toArray();
    }
}