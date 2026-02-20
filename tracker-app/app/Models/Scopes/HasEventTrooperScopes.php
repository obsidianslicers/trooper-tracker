<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventTrooperStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the EventTrooper model.
 *
 * Provides query scopes for loading event trooper data with relationships
 * optimized for roster and attendance management views.
 */
trait HasEventTrooperScopes
{
    /**
     * Scope a query to only include troopers (non-handlers) with GOING status.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeTroopers(Builder $query): Builder
    {
        return $query->where(self::STATUS, EventTrooperStatus::GOING)
            ->where(self::IS_HANDLER, false);
    }

    /**
     * Scope a query to only include handlers with GOING status.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeHandlers(Builder $query): Builder
    {
        return $query->where(self::STATUS, EventTrooperStatus::GOING)
            ->where(self::IS_HANDLER, true);
    }
}