<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Resources;

use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A costume found by the costume search (search.costumes), for the costume picker and the
 * account page's Add Costume: its name and the clubs that recognise it.
 *
 * @mixin Costume
 */
class CostumeSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->id,
            Costume::NAME => $this->name,
            'organizations' => $this->organization_costumes
                ->map(fn ($organization_costume): array => [
                    Organization::ID => $organization_costume->organization->id,
                    Organization::NAME => $organization_costume->organization->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
