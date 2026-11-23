<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The totals row under the completed and cancelled events lists, added up over the events shown.
 *
 * @property Collection<int, Event> $resource The events on the page, with their summary counts
 */
class PastEventTotalsResource extends JsonResource
{
    /**
     * @return array{shifts: int, unique_troopers: int, total_troopers: int, direct_funds: float, indirect_funds: float}
     */
    public function toArray(Request $request): array
    {
        return [
            'shifts' => (int) $this->resource->sum('event_shifts_count'),
            'unique_troopers' => (int) $this->resource->sum('unique_trooper_count'),
            'total_troopers' => (int) $this->resource->sum('total_trooper_count'),
            'direct_funds' => (float) $this->resource->sum('charity_direct_funds'),
            'indirect_funds' => (float) $this->resource->sum('charity_indirect_funds'),
        ];
    }
}
