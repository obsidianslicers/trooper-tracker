<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\TrooperCostume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Costume Approvals queue.
 *
 * @mixin TrooperCostume
 */
class PendingCostumeRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            TrooperCostume::ID => $this->id,
            'trooper' => ['id' => $this->trooper->id, 'name' => $this->trooper->display_name],
            'costume' => $this->organization_costume->costume->name,
            'club' => $this->organization_costume->organization->name,
            'requested_at' => $this->created_at?->localFormat('M j, Y'),
        ];
    }
}
