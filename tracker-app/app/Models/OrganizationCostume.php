<?php

namespace App\Models;

use App\Models\Base\OrganizationCostume as BaseOrganizationCostume;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasOrganizationCostumeScopes;
use App\Models\Scopes\HasToOptionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationCostume extends BaseOrganizationCostume
{
    use HasToOptionScope;
    use HasOrganizationCostumeScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the full name of the costume, including the organization if available.
     *
     * This accessor returns the costume name. If the 'organization' relationship
     * is loaded, it prepends the organization's name in parentheses.
     * e.g., "(501st Legion) Stormtrooper" or "Stormtrooper".
     *
     * @return string The full name of the costume.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->relationLoaded('organization') && $this->organization)
        {
            return "({$this->organization->name}) {$this->name}";
        }

        return $this->name;
    }
}
