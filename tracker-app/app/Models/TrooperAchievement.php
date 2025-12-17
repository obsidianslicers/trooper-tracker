<?php

namespace App\Models;

use App\Models\Base\TrooperAchievement as BaseTrooperAchievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents an achievement or milestone earned by a trooper.
 *
 * Achievements track special accomplishments or milestones that troopers
 * reach, such as event count milestones, special recognitions, or participation
 * in unique activities.
 */
class TrooperAchievement extends BaseTrooperAchievement
{
    use HasFactory;
}
