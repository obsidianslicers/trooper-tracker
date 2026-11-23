<?php

declare(strict_types=1);

namespace App\Messages\System\Resources;

use App\Services\SystemCheckResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A group of checks on the System Check page (admin/SystemCheck).
 *
 * @property array{name: string, results: Collection<int, SystemCheckResult>} $resource
 */
class SystemCheckGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'],
            'results' => SystemCheckResultResource::collection($this->resource['results']->values()),
        ];
    }
}
