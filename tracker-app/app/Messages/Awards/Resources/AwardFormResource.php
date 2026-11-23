<?php

declare(strict_types=1);

namespace App\Messages\Awards\Resources;

use App\Models\Award;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The starting values of the Command Staff create award form: blank, or a copy of another award.
 *
 * @mixin Award
 */
class AwardFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Award::ORGANIZATION_ID => $this->organization_id,
            Award::FREQUENCY => $this->frequency->value,
            Award::NAME => $this->name ?? '',
        ];
    }
}
