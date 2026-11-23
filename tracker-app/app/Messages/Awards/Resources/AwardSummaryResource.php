<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The award shown above the Command Staff award pages (troopers awarded, award a trooper).
 *
 * @mixin Award
 */
class AwardSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Award::ID => $this->id,
            Award::NAME => $this->name ?? '',
            'organization_name' => $this->organization?->name,
            'frequency_label' => $this->frequency->label(),
        ];
    }
}
