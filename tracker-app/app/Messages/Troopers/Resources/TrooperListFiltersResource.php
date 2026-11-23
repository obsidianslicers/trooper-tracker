<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The filters and sort in use on the Command Staff troopers list (admin/troopers/List), so the
 * page can show them and keep them when it builds the next link.
 *
 * @property array{search_term: string, membership_role: string|null, organization_id: int|null, sort_col: string, sort_dir: string} $resource
 */
class TrooperListFiltersResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'search_term' => $this->resource['search_term'],
            'membership_role' => $this->resource['membership_role'],
            'organization_id' => $this->resource['organization_id'],
            'sort_col' => $this->resource['sort_col'],
            'sort_dir' => $this->resource['sort_dir'],
        ];
    }
}
