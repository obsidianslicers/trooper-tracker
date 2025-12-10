<?php

namespace App\Models;

use App\Models\Base\AwardTrooper as BaseAwardTrooper;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasAwardTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AwardTrooper extends BaseAwardTrooper
{
    use HasAwardTrooperScopes;
    use HasFactory;
    use HasTrooperStamps;
}
