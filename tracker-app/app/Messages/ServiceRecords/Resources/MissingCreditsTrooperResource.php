<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The trooper the missing-credit tool is narrowed to (admin/service-records/MissingCredits).
 *
 * @property Trooper $resource
 */
class MissingCreditsTrooperResource extends JsonResource
{
    /**
     * @return array{id: int, display_name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::ID => $this->resource->id,
            Trooper::DISPLAY_NAME => $this->resource->display_name,
        ];
    }
}
