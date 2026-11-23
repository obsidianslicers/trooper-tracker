<?php

declare(strict_types=1);

namespace App\Messages\System\Resources;

use App\Services\SystemCheckResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One check on the System Check page (admin/SystemCheck). Diagnostics stay in English.
 *
 * @property SystemCheckResult $resource
 */
class SystemCheckResultResource extends JsonResource
{
    /**
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource->label,
            'detail' => $this->resource->detail,
            'status' => ucfirst($this->resource->status->value),
            'badge' => $this->resource->status->badgeClass(),
            'icon' => $this->resource->status->icon(),
        ];
    }
}
