<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club that can offer the costume, as a checkbox on the Command Staff costume update page.
 *
 * @mixin Organization
 */
class CostumeClubResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::ID => $this->id,
            Organization::NAME => $this->name,
        ];
    }
}
