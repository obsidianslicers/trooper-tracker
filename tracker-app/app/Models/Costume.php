<?php

namespace App\Models;

use App\Models\Scopes\HasCostumeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Costume as BaseCostume;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Costume extends BaseCostume
{
    use HasFactory;
    use HasCostumeScopes;

    public function organization_costumes(): HasMany
    {
        return $this->hasMany(OrganizationCostume::class);
    }
}
