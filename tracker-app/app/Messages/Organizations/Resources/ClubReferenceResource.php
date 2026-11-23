<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The club the Command Staff organization costumes tab belongs to: only its id, for the tabs.
 *
 * @mixin Organization
 */
class ClubReferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::ID => $this->id,
        ];
    }
}
