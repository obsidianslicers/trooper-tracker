<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One trooper on the Troopers Without Activity report (admin/reports/TroopersWithoutActivity).
 *
 * @property Trooper $resource
 */
class InactiveTrooperRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::ID => $this->resource->id,
            Trooper::DISPLAY_NAME => $this->resource->display_name,
            'profile_url' => route('admin.troopers.show', ['trooper' => $this->resource]),
            'last_seen' => $this->resource->last_active_at?->localFormat('M d, Y') ?? __('admin.Never'),
        ];
    }
}
