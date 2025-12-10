<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\AwardTrooper;
use Illuminate\Database\Eloquent\Builder;

trait HasAwardTrooperScopes
{
    protected function scopeByTrooper(Builder $query, int $trooper_id): Builder
    {
        return $query->with('award:id,name')
            ->where(self::TROOPER_ID, $trooper_id)
            ->orderBy(self::CREATED_AT, "desc");
    }
}