<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pivot model for awards assigned to troopers.
 *
 * Provides casting for award-specific metadata on the pivot.
 */
class AwardTrooperPivot extends Pivot
{
    use SoftDeletes;

    protected $casts = [
        'award_date' => 'datetime',
    ];
}
