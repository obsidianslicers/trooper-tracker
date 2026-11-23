<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The organization a Command Staff list (awards, notices) is filtered to, shown as a badge that
 * clears the filter.
 *
 * @mixin Organization
 */
class OrganizationFilterResource extends JsonResource
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
