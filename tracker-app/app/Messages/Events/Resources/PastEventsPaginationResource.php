<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The page links under the completed events list.
 *
 * @property LengthAwarePaginator<int, Event> $resource
 */
class PastEventsPaginationResource extends JsonResource
{
    /**
     * @return array{current_page: int, last_page: int, total: int, prev_url: string|null, next_url: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'current_page' => $this->resource->currentPage(),
            'last_page' => $this->resource->lastPage(),
            'total' => $this->resource->total(),
            'prev_url' => $this->resource->previousPageUrl(),
            'next_url' => $this->resource->nextPageUrl(),
        ];
    }
}
