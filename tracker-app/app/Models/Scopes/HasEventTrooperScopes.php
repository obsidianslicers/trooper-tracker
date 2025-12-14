<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the EventTrooper model.
 *
 * Provides query scopes for loading event trooper data with relationships
 * optimized for roster and attendance management views.
 */
trait HasEventTrooperScopes
{
    public function scopeTroopers(Builder $query): Builder
    {
        return $query->where(self::STATUS, EventTrooperStatus::GOING)
            ->whereHas('trooper', function (Builder $q)
            {
                $q->where(Trooper::MEMBERSHIP_ROLE, '!=', MembershipRole::HANDLER);
            });
    }

    public function scopeHandlers(Builder $query): Builder
    {
        return $query->where(self::STATUS, EventTrooperStatus::GOING)
            ->whereHas('trooper', function (Builder $q)
            {
                $q->where(Trooper::MEMBERSHIP_ROLE, MembershipRole::HANDLER);
            });
    }
}