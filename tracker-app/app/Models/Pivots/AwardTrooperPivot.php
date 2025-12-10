<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AwardTrooperPivot extends Pivot
{
    protected $casts = [
        'award_date' => 'datetime',
    ];
}
