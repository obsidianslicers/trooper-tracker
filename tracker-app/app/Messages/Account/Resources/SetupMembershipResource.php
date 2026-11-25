<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club the trooper already belongs to and where they're assigned, on the setup form
 * (account/Setup).
 *
 * @mixin Organization
 */
class SetupMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::ID => $this->resource->id,
            Organization::NAME => $this->resource->name,
            'assignment' => $this->resource->assignment->name,
        ];
    }
}
