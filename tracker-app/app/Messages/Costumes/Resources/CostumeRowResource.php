<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Resources;

use App\Models\Costume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff costume list.
 *
 * @mixin Costume
 */
class CostumeRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->id,
            Costume::NAME => $this->name,
            'organizations' => $this->organizations->pluck('name')->implode(', '),
            'can_delete' => !$this->countsAsHandler(),
        ];
    }
}
