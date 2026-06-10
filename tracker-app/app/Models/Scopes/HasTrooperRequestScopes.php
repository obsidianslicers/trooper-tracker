<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\TrooperRequestStatus;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait HasTrooperRequestScopes
{
    public function scopePending(Builder $query): Builder
    {
        return $query->where(self::STATUS, TrooperRequestStatus::PENDING);
    }

    public function scopeDenied(Builder $query): Builder
    {
        return $query->where(self::STATUS, TrooperRequestStatus::DENIED);
    }

    public function scopeForModerator(Builder $query, Trooper $moderator): Builder
    {
        if ($moderator->is_administrator)
        {
            return $query;
        }

        return $query->whereExists(function (QueryBuilder $sub) use ($moderator)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_mod')
                ->join('tt_organizations as org_mod', 'ta_mod.organization_id', '=', 'org_mod.id')
                ->join('tt_organizations as org_req', 'org_req.id', '=', 'tt_trooper_requests.organization_id')
                ->where('ta_mod.trooper_id', $moderator->id)
                ->where('ta_mod.is_moderator', true)
                ->whereRaw('org_req.node_path LIKE CONCAT(org_mod.node_path, "%")');
        });
    }
}
