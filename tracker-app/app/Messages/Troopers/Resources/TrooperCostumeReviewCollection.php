<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\TrooperCostume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperCostumeReviewCollection extends ResourceCollection
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(function (TrooperCostume $trooper_costume): array
            {
                return [
                    'id' => $trooper_costume->id,
                    'costume' => $trooper_costume->organization_costume?->costume?->name,
                    'organization' => $trooper_costume->organization_costume?->organization?->name,
                    'status' => $trooper_costume->status,
                    'is_approved' => in_array($trooper_costume->status, ['approved', 'club_records'], true),
                    'by' => $trooper_costume->trashed() && $trooper_costume->isRevoked()
                        ? $trooper_costume->revoked_by?->display_name
                        : $trooper_costume->approved_by?->display_name,
                    'can_review' => (bool) $trooper_costume->can_review,
                ];
            })->values()->all();
    }
}
