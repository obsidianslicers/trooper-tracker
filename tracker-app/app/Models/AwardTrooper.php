<?php

namespace App\Models;

use App\Models\Base\AwardTrooper as BaseAwardTrooper;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasAwardTrooperScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents the pivot relationship between awards and troopers.
 *
 * This model tracks when a specific award was given to a specific trooper,
 * including any additional metadata about the award instance such as
 * the date awarded and the trooper who granted the award.
 */
class AwardTrooper extends BaseAwardTrooper
{
    use HasAwardTrooperScopes;
    use HasFactory;
    use HasTrooperStamps;
}
