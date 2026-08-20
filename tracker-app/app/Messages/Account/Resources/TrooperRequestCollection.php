<?php

namespace App\Messages\Account\Resources;

use App\Models\TrooperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperRequestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(TrooperRequest $trooper_request) => [
                'membership_path' => $trooper_request->membership_path,
                'identifier' => $trooper_request->identifier,
                'status' => $trooper_request->status,
                'created' => $trooper_request->created_at->diffForHumans(),
                'updated' => $trooper_request->updated_at->diffForHumans(),
                'denial_reason' => $trooper_request->denial_reason,
                'image_url' => map_image_url(
                    path: $trooper_request->primary_organization->image_path_sm,
                    default: DEFAULT_ORGANIZATION_IMAGE_URL
                ),
            ])->toArray();
    }
}