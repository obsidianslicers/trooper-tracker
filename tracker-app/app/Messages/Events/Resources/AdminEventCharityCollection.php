<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\EventShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Each shift's charity numbers for the Charity tab.
 */
class AdminEventCharityCollection extends ResourceCollection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (EventShift $shift): array => [
                'id' => $shift->id,
                'date' => $shift->shift_starts_at->localFormat('D, M j, Y'),
                'time' => __('admin.:start to :end', [
                    'start' => $shift->shift_starts_at->localFormat('g:i A'),
                    'end' => $shift->shift_ends_at->localFormat('g:i A'),
                ]),
                'shift_hours' => (int) $shift->shift_starts_at->diffInHours($shift->shift_ends_at),
                'charity_name' => $shift->charity_name ?? '',
                'charity_hours' => $shift->charity_hours === null ? '' : (string) $shift->charity_hours,
                'charity_direct_funds' => format_funds($shift->charity_direct_funds, ''),
                'charity_indirect_funds' => format_funds($shift->charity_indirect_funds, ''),
                'charity_notes' => $shift->charity_notes ?? '',
            ])
            ->values()
            ->all();
    }
}
