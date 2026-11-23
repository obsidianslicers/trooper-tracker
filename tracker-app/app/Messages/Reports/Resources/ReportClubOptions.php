<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The clubs a report can be narrowed to, as a flat list of options (admin/reports summaries).
 *
 * @property \Illuminate\Support\Collection<int, Organization> $collection
 */
class ReportClubOptions extends ResourceCollection
{
    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Organization $organization): array => [
                'value' => $organization->id,
                'label' => $organization->name,
            ])
            ->values()
            ->all();
    }
}
