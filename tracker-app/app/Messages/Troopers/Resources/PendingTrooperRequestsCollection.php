<?php

namespace App\Messages\Troopers\Resources;

use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PendingTrooperRequestsCollection extends ResourceCollection
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
                'id' => $trooper_request->id,
                'identifier' => $trooper_request->identifier,
                'status' => $trooper_request->status,
                'denial_reason' => $trooper_request->denial_reason,
                'organization' => $this->getOrganization($trooper_request),
                'primary_organization' => $this->getPrimaryOrganization($trooper_request),
                'trooper' => $this->getTrooper($trooper_request),
            ])->toArray();
    }

    private function getOrganization(TrooperRequest $trooper_request): array
    {
        return [
            'id' => $trooper_request->organization->id,
            'name' => $trooper_request->organization->name,
            'parent_name' => $trooper_request->organization->parent?->name,
        ];
    }

    private function getPrimaryOrganization(TrooperRequest $trooper_request): array
    {
        return [
            'id' => $trooper_request->primary_organization->id,
            'name' => $trooper_request->primary_organization->name,
            'parent_name' => $trooper_request->primary_organization->parent?->name,
        ];
    }

    private function getTrooper(TrooperRequest $trooper_request): array
    {
        return [
            'id' => $trooper_request->trooper->id,
            'display_name' => $trooper_request->trooper->display_name,
            'legal_name' => $trooper_request->trooper->legal_name,
            'email' => $trooper_request->trooper->email,
            'phone' => $trooper_request->trooper->phone,
        ];
    }
}