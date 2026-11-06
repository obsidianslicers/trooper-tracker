<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\TrooperCostume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Hides trooper costumes that are waiting for officer approval.
 *
 * Credit, sign-up pickers and profiles all read trooper costumes through this
 * model, so a pending costume can't be used anywhere until it is approved.
 * Code that manages the approval itself opts back in with withPending() or
 * onlyPending().
 */
class ApprovedTrooperCostumeScope implements Scope
{
    /**
     * @param  Builder<TrooperCostume>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull($model->qualifyColumn(TrooperCostume::APPROVED_AT));
    }

    /**
     * @param  Builder<TrooperCostume>  $builder
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withPending', function (Builder $builder): Builder
        {
            return $builder->withoutGlobalScope(ApprovedTrooperCostumeScope::class);
        });

        $builder->macro('onlyPending', function (Builder $builder): Builder
        {
            return $builder->withoutGlobalScope(ApprovedTrooperCostumeScope::class)
                ->whereNull($builder->getModel()->qualifyColumn(TrooperCostume::APPROVED_AT));
        });
    }
}
