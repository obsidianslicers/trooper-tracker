<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The organization the Command Staff troopers list (admin/troopers/List) is narrowed to, named in
 * the badge that clears it.
 *
 * @mixin Organization
 */
class TrooperListOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::NAME => $this->name,
        ];
    }
}
