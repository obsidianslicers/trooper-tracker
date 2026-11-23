<?php

declare(strict_types=1);

namespace App\Messages\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A tool on the Command Staff dashboard (admin/Dashboard): the key the page turns into its title
 * and text, and where it goes.
 *
 * @property array{key: string, route: string} $resource
 */
class DashboardCardResource extends JsonResource
{
    /**
     * @return array{key: string, url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource['key'],
            'url' => route($this->resource['route']),
        ];
    }
}
