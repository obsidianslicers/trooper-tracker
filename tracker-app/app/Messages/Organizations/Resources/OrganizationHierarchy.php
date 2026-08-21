<?php

namespace App\Messages\Organizations\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationHierarchy extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Organization $org) => [
                'id' => $org->id,
                'name' => $org->name,
                'identifier_display' => $org->identifier_display,
                'identifier_validation' => $org->identifier_validation,
                'regions' => $org->organizations->map(fn ($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'parent_id' => $region->parent_id,
                    'primary_organization_id' => $org->id,
                    'units' => $region->organizations->map(fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'parent_id' => $unit->parent_id,
                        'primary_organization_id' => $org->id,
                    ]),
                ]),
            ])->toArray();
    }
}
