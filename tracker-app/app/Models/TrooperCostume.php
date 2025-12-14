<?php

namespace App\Models;

use App\Models\Base\TrooperCostume as BaseTrooperCostume;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperCostumeScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrooperCostume extends BaseTrooperCostume
{
    use HasTrooperCostumeScopes;
    use HasFactory;
    use HasTrooperStamps;
}
