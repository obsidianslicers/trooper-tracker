<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\EventUploadTrooper;
use App\Models\EventUpload;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the EventUpload model.
 */
trait HasEventUploadScopes
{
    /**
     * Scope a query to only include uploads for a specific event.
     *
     * @param Builder<EventUpload> $query The Eloquent query builder.
     * @param int $event_id The ID of the event to filter by.
     * @return Builder<EventUpload>
     */
    public function scopeByEvent(Builder $query, int $event_id): Builder
    {
        return $query->with('troopers')
            ->where(EventUpload::EVENT_ID, $event_id);
    }

    /**
     * Scope a query to only include uploads tagged with a specific trooper.
     *
     * @param Builder<EventUpload> $query The Eloquent query builder.
     * @param int $trooper_id The ID of the trooper to filter by.
     * @return Builder<EventUpload>
     */
    public function scopeByTrooper(Builder $query, int $trooper_id): Builder
    {
        return $query->with('troopers')
            ->whereHas('troopers', fn($q) => $q->where(EventUploadTrooper::TROOPER_ID, $trooper_id));
    }
}