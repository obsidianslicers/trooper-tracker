<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AwardFrequency;
use App\Models\Base\Award as BaseAward;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\AwardTrooper;
use App\Models\Pivots\AwardTrooperPivot;
use App\Models\Scopes\HasAwardScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents an award that can be given to troopers.
 *
 * Awards are recognitions given to members for their participation, achievements,
 * or milestones. Each award has a frequency (one-time, annual, etc.) and can be
 * awarded to multiple troopers through a pivot relationship.
 */
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

    /**
     * Get the troopers that have been awarded this award.
     *
     * @return BelongsToMany<Trooper>
     */
    public function troopers(): BelongsToMany
    {
        return parent::troopers()
            ->using(AwardTrooperPivot::class)
            ->wherePivotNull(AwardTrooper::DELETED_AT);
    }
}
