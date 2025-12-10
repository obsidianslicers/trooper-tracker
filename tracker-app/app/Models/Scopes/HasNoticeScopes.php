<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the Notice model.
 */
trait HasNoticeScopes
{
    /**
     * Scope a query to only include notices that are currently active.
     *
     * An active notice has a `starts_at` date in the past and an `ends_at` date
     * that is either null or in the future.
     *
     * @param Builder $query The Eloquent query builder.
     * @return Builder<self>
     */
    protected function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where(self::STARTS_AT, '<=', $now)
            ->where(function ($q) use ($now)
            {
                $q->whereNull(self::ENDS_AT)
                    ->orWhere(self::ENDS_AT, '>=', $now);
            });
    }

    /**
     * Scope a query to only include notices that have already expired.
     *
     * A past notice has both `starts_at` and `ends_at` dates in the past.
     *
     * @param Builder $query The Eloquent query builder.
     * @return Builder<self>
     */
    protected function scopePast(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where(self::STARTS_AT, '<', $now)
            ->where(self::ENDS_AT, '<', $now);
    }

    /**
     * Scope a query to only include notices scheduled for the future.
     *
     * A future notice has both `starts_at` and `ends_at` dates in the future.
     *
     * @param Builder $query The Eloquent query builder.
     * @return Builder<self>
     */
    protected function scopeFuture(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where(self::STARTS_AT, '>', $now)
            ->where(self::ENDS_AT, '>', $now);
    }

    /**
     * Scope a query to only include notifications visible to a given trooper.
     *
     * A notice is visible if it is global (no organization) or if the trooper is a
     * member of an organization within the notice's organizational hierarchy.
     * Optionally, it can filter for unread notices only.
     *
     * @param Builder $query The Eloquent query builder.
     * @param Trooper $trooper The trooper whose visibility is being checked.
     * @return Builder<self>
     */
    protected function scopeVisibleTo(Builder $query, Trooper $trooper, bool $unread_only = false): Builder
    {
        $query->where(function ($outer) use ($trooper)
        {
            $outer->whereExists(function ($sub) use ($trooper)
            {
                $sub->select(DB::raw(1))
                    ->from('tt_trooper_assignments as ta_assign')
                    ->join('tt_organizations as org_assign', 'ta_assign.organization_id', '=', 'org_assign.id')
                    ->join('tt_organizations as org_notice', 'tt_notices.organization_id', '=', 'org_notice.id')
                    ->where('ta_assign.trooper_id', $trooper->id)
                    ->where('ta_assign.is_member', true)
                    ->whereRaw('org_assign.node_path LIKE CONCAT(org_notice.node_path, "%")');
            })->orWhereNull('tt_notices.organization_id');
        });


        if ($unread_only)
        {
            $query->where(function ($outer) use ($trooper)
            {
                $outer->whereDoesntHave('troopers', function ($sub) use ($trooper)
                {
                    $sub->where('tt_notice_troopers.trooper_id', $trooper->id);
                })
                    ->orWhereHas('troopers', function ($sub) use ($trooper)
                    {
                        $sub->where('tt_notice_troopers.trooper_id', $trooper->id)
                            ->where('tt_notice_troopers.is_read', false);
                    });
            });
        }

        return $query;
    }

    /**
     * Scope a query to only include notices that can be managed by a given moderator.
     *
     * A notice is moderated if its organization falls within the moderator's
     * assigned organizational hierarchy.
     *
     * @param Builder $query The Eloquent query builder.
     * @param Trooper $trooper The moderator to filter by.
     * @return Builder
     */
    protected function scopeModeratedBy(Builder $query, Trooper $trooper): Builder
    {
        if ($trooper->isAdministrator())
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($trooper)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organizations as org_notice', 'tt_notices.organization_id', '=', 'org_notice.id')
                ->where('ta_moderator.trooper_id', $trooper->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereRaw('org_notice.node_path LIKE CONCAT(org_moderator.node_path, "%")');
        });
    }
}