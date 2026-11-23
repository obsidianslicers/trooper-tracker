<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The clubs on the account page's Memberships tab (account/components/MembershipSelection). A
 * request picks a club, region or unit from the options; this says which club each id belongs
 * to, for the identifier label and its validation.
 */
class MembershipOrganizationCollection extends ResourceCollection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Organization $org): array => [
                Organization::ID => $org->id,
                Organization::NAME => $org->name,
                Organization::IDENTIFIER_DISPLAY => $org->identifier_display,
                Organization::IDENTIFIER_VALIDATION => $org->identifier_validation,
                'regions' => $org->organizations->map(fn (Organization $region): array => [
                    Organization::ID => $region->id,
                    'units' => $region->organizations
                        ->map(fn (Organization $unit): array => [Organization::ID => $unit->id])
                        ->values()
                        ->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
