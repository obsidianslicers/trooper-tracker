<?php

declare(strict_types=1);

namespace App\Messages\Auth\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club on the registration page (auth/Register), with its regions and their units to pick from.
 *
 * @mixin Organization
 */
class RegisterOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::ID => $this->id,
            Organization::NAME => $this->name,
            Organization::IDENTIFIER_DISPLAY => $this->identifier_display,
            Organization::REQUIRES_GUARDIAN => $this->requires_guardian,
            'regions' => $this->organizations->map(fn (Organization $region): array => [
                Organization::ID => $region->id,
                Organization::NAME => $region->name,
                'units' => $region->organizations->map(fn (Organization $unit): array => [
                    Organization::ID => $unit->id,
                    Organization::NAME => $unit->name,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
