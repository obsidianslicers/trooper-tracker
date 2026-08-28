<?php

namespace App\Messages\Troopers\Resources;

use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PendingTrooperApprovalsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn(Trooper $trooper) => [
                'id' => $trooper->id,
                'is_visitor' => $trooper->is_visitor,
                'is_denied' => $trooper->is_denied,
                'is_active' => $trooper->is_active,
                'trooper_id' => $trooper->id,
                'display_name' => $trooper->display_name,
                'legal_name' => $trooper->legal_name,
                'email' => $trooper->email,
                'phone' => $trooper->phone,
                'visitor_expires_at' => $trooper->visitor_expires_at,
                'visitor_expires_diff_for_humans' => $trooper->visitor_expires_at?->diffForHumans(),
                'membership_role' => $trooper->membership_role,
                'is_minor' => $trooper->is_minor,
                'guardian' => $this->getGuardian($trooper),
                'trooper_requests' => $this->getTrooperRequests($trooper),
            ])->toArray();
    }

    private function getGuardian(Trooper $trooper): ?array
    {
        if (!$trooper->is_minor)
        {
            return null;
        }

        $guardian = $trooper->guardian;

        if (!$guardian)
        {
            return null;
        }

        return [
            'trooper_id' => $guardian->id,
            'display_name' => $guardian->display_name,
            'legal_name' => $guardian->legal_name,
            'email' => $guardian->email,
            'phone' => $guardian->phone,
        ];
    }

    private function getTrooperRequests(Trooper $trooper): array
    {
        return $trooper->trooper_requests->map(fn(TrooperRequest $request) => [
            'identifier' => $request->identifier,
            'organization' => [
                'name' => $request->organization->name,
                'parent_name' => $request->organization->parent?->name,
            ],
            'primary_organization' => [
                'requires_guardian' => $request->primary_organization->requires_guardian,
                'name' => $request->primary_organization->name,
            ]
        ])->toArray();
    }
}
