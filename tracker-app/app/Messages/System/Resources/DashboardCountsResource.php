<?php

declare(strict_types=1);

namespace App\Messages\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What's waiting for the reader's review, for the badges on the Command Staff dashboard
 * (admin/Dashboard).
 *
 * @property array{not_approved: int, pending_join_requests: int, pending_costumes: int, troops_awaiting_verification: int, pending_permission_requests: int} $resource
 */
class DashboardCountsResource extends JsonResource
{
    /**
     * @return array<string, int>
     */
    public function toArray(Request $request): array
    {
        return [
            'not_approved' => $this->resource['not_approved'],
            'pending_join_requests' => $this->resource['pending_join_requests'],
            'pending_costumes' => $this->resource['pending_costumes'],
            'troops_awaiting_verification' => $this->resource['troops_awaiting_verification'],
            'pending_permission_requests' => $this->resource['pending_permission_requests'],
        ];
    }
}
