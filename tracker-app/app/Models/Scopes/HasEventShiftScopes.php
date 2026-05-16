<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the EventShift model.
 *
 * This trait provides query scopes for filtering event shifts by various criteria
 * including status, trooper participation, and roster information for event management.
 */
trait HasEventShiftScopes
{
    /**
     * Scope a query to only include active event shifts.
     *
     * Active shifts are those with status of OPEN, DRAFT, or SIGN_UP_LOCKED
     * that haven't ended yet, ordered by their start time.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        $status_list = [
            EventStatus::OPEN,
            EventStatus::MANUAL_SELECTION,
            EventStatus::DRAFT,
            EventStatus::SIGN_UP_LOCKED,
        ];

        return $query->whereIn(self::STATUS, $status_list);
    }

    /**
     * Scope a query to find events a specific trooper is signed up for.
     *
     * This scope filters events based on a trooper's participation and whether the
     * event is open or closed. It also eagerly loads the relevant relationships
     * for displaying the event details for that trooper.
     *
     * @param Builder<self> $query The Eloquent query builder.
     * @param int $trooper_id The ID of the trooper to filter by.
     * @param bool $closed True to fetch closed (historical) events, false for open events.
     * @return Builder<self>
     */
    public function scopeByTrooper(Builder $query, int $trooper_id, bool $closed): Builder
    {
        $excluded_statuses = [EventTrooperStatus::CANCELLED, EventTrooperStatus::NOT_PICKED];

        $with = [
            'event_troopers' => function ($q) use ($trooper_id, $excluded_statuses)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id)
                    ->whereNotIn(EventTrooper::STATUS, $excluded_statuses)
                    ->with('costume');
            },
        ];

        if ($closed)
        {
            return $query->with($with)
                ->where(self::STATUS, EventStatus::CLOSED)
                ->whereHas('event_troopers', function ($q) use ($trooper_id, $excluded_statuses)
                {
                    $q->where(EventTrooper::TROOPER_ID, $trooper_id)
                        ->whereNotIn(EventTrooper::STATUS, $excluded_statuses);
                });
        }

        return $query->with($with)
            ->whereIn(self::STATUS, [EventStatus::OPEN, EventStatus::MANUAL_SELECTION])
            ->whereHas('event_troopers', function ($q) use ($trooper_id, $excluded_statuses)
            {
                $q->where(EventTrooper::TROOPER_ID, $trooper_id)
                    ->whereNotIn(EventTrooper::STATUS, $excluded_statuses);
            });
    }
}