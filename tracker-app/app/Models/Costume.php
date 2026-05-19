<?php

namespace App\Models;

use App\Models\Concerns\HasFilter;
use App\Models\Scopes\HasCostumeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Costume as BaseCostume;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a costume definition that troopers can own or be approved for.
 *
 * Costumes define the canonical costume types used across organizations
 * and assignments, including special roles like handlers.
 */
class Costume extends BaseCostume
{
    use HasFactory;
    use HasFilter;
    use HasCostumeScopes;

    public const string COMMAND_STAFF = 'Command Staff';
    public const string HANDLER = 'Handler';

    /**
     * Get the organization-specific costume variants for this costume.
     *
     * @return HasMany<OrganizationCostume>
     */
    public function countsAsHandler(): bool
    {
        return in_array($this->name, [self::HANDLER, self::COMMAND_STAFF]);
    }

    public function organization_costumes(): HasMany
    {
        return $this->hasMany(OrganizationCostume::class);
    }

}
