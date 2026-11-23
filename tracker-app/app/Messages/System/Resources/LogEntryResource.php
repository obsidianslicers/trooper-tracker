<?php

declare(strict_types=1);

namespace App\Messages\System\Resources;

use App\Services\LogEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A recent error from the log, on the System Check page (admin/SystemCheck).
 *
 * @property LogEntry $resource
 */
class LogEntryResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'level' => strtoupper($this->resource->level),
            'badge' => $this->resource->badgeClass(),
            'logged_at' => $this->resource->logged_at,
            'summary' => $this->resource->summary(),
            'message' => $this->resource->message,
        ];
    }
}
