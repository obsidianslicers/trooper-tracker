<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperAuthorityOrganizationCollection extends ResourceCollection
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(function (Organization $organization): array
            {
                $assignment = $organization->trooper_assignments->first();

                return [
                    'id' => $organization->id,
                    'parent_id' => $organization->parent_id,
                    'depth' => (int) $organization->depth,
                    'name' => $organization->name,
                    'is_moderator' => (bool) $assignment?->is_moderator,
                    'should_notify' => (bool) $assignment?->should_notify,
                    'is_member' => (bool) $assignment?->is_member,
                ];
            })->values()->all();
    }
}
