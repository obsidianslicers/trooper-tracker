<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\TrooperCostume as BaseTrooperCostume;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\ApprovedTrooperCostumeScope;
use App\Models\Scopes\HasTrooperCostumeScopes;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a costume owned by a trooper.
 *
 * This model tracks a trooper's personal costume collection, linking their
 * owned costumes to organization costume types. A row with no approved_at is
 * waiting for an officer and is hidden by ApprovedTrooperCostumeScope.
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> withPending()
 * @method static \Illuminate\Database\Eloquent\Builder<static> onlyPending()
 */
#[ScopedBy([ApprovedTrooperCostumeScope::class])]
class TrooperCostume extends BaseTrooperCostume
{
    use HasFactory;
    use HasTrooperCostumeScopes;
    use HasTrooperStamps;

    protected static function booted(): void
    {
        //  rows are approved unless the caller explicitly creates them pending
        static::creating(function (TrooperCostume $trooper_costume): void
        {
            if (!array_key_exists(self::APPROVED_AT, $trooper_costume->getAttributes()))
            {
                $trooper_costume->{self::APPROVED_AT} = now();
            }
        });
    }

    /**
     * Bound routes are the review screens, so pending costumes must resolve too.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query->withPending(), $value, $field);
    }

    public function isReviewableBy(Trooper $moderator): bool
    {
        return self::withPending()
            ->whereKey($this->id)
            ->moderatedBy($moderator)
            ->exists();
    }

    public function approved_by(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::APPROVED_ID);
    }

    public function isPending(): bool
    {
        return $this->{self::APPROVED_AT} === null;
    }

    public function markApproved(?Trooper $approver): void
    {
        $this->{self::APPROVED_AT} = now();
        $this->{self::APPROVED_ID} = $approver?->id;
    }

    public function markPending(): void
    {
        $this->{self::APPROVED_AT} = null;
        $this->{self::APPROVED_ID} = null;
    }
}
