<?php

namespace App\Models;

use App\Models\Base\TrooperCostume as BaseTrooperCostume;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperCostumeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a costume owned by a trooper.
 *
 * This model tracks a trooper's personal costume collection, linking their
 * owned costumes to organization costume types. It enables costume-specific
 * event participation and costume approval tracking.
 */
class TrooperCostume extends BaseTrooperCostume
{
    use HasTrooperCostumeScopes;
    use HasFactory;
    use HasTrooperStamps;
}
