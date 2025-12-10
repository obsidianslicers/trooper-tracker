<?php

namespace App\Models;

use App\Enums\AwardFrequency;
use App\Models\Base\Award as BaseAward;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Pivots\AwardTrooperPivot;
use App\Models\Scopes\HasAwardScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Award extends BaseAward
{
    use HasFilter;
    use HasAwardScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::FREQUENCY => AwardFrequency::class,
        ]);
    }

    public function troopers(): BelongsToMany
    {
        return parent::troopers()->using(AwardTrooperPivot::class);
    }
}
