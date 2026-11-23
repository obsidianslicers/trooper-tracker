<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The filters the Command Staff events list was opened with, so its links can keep them.
 *
 * @property array{status: string|null, search_term: string|null, organization_id: int|null} $resource
 */
class AdminEventListFilters extends JsonResource
{
    public function __construct(?string $status, ?string $search_term, ?int $organization_id)
    {
        parent::__construct(compact('status', 'search_term', 'organization_id'));
    }

    /**
     * @return array{status: string|null, search_term: string, organization_id: int|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource['status'],
            'search_term' => $this->resource['search_term'] ?? '',
            'organization_id' => $this->resource['organization_id'],
        ];
    }
}
