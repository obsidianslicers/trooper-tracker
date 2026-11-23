<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The organization the Command Staff events list is filtered to, named in the list's header.
 *
 * @mixin Organization
 */
class AdminEventListOrganization extends JsonResource
{
    /**
     * @return array{name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::NAME => $this->name,
        ];
    }
}
