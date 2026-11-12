<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperRecentEventCollection extends ResourceCollection
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(function (Organization $organization): array
            {
                $shift = $organization->event_shift;

                return [
                    'organization' => $organization->name,
                    'event_id' => $shift?->event->id,
                    'event_name' => $shift?->event->name,
                    'shift_date' => $shift?->shift_starts_at->format('M d, Y'),
                    'is_old' => $shift !== null && $shift->shift_starts_at->lt(now()->subYear()),
                ];
            })->values()->all();
    }
}
