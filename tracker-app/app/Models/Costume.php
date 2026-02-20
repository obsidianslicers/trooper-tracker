<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Costume as BaseCostume;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Costume extends BaseCostume
{
    use HasFactory;

    public function organization_costumes(): HasMany
    {
        return $this->hasMany(OrganizationCostume::class);
    }
}
