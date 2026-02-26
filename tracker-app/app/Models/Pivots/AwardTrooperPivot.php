<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for awards assigned to troopers.
 *
 * Provides casting for award-specific metadata on the pivot.
 */
class AwardTrooperPivot extends Pivot
{
    protected $casts = [
        'award_date' => 'datetime',
    ];
}
