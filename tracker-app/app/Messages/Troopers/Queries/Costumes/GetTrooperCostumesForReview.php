<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Costumes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * A trooper's costume per club, approved and pending, each marked with whether the
 * moderator may review it.
 */
final class GetTrooperCostumesForReview extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Trooper $moderator,
    ) {}

    /**
     * @return Collection<int, TrooperCostume>
     */
    public function handle(): Collection
    {
        $reviewable_ids = TrooperCostume::withPending()
            ->withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->moderatedBy($this->moderator)
            ->pluck(TrooperCostume::ID)
            ->all();

        return TrooperCostume::withPending()
            ->withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->where(fn ($query) => $query->whereNull(TrooperCostume::DELETED_AT)
                ->orWhereNotNull(TrooperCostume::REVOKED_AT))
            ->with([
                'organization_costume.costume',
                'organization_costume.organization',
                'approved_by',
                'revoked_by',
            ])
            ->get()
            ->sortBy(fn (TrooperCostume $tc) => $tc->organization_costume?->costume?->name)
            ->values()
            ->each(function (TrooperCostume $tc) use ($reviewable_ids): void
            {
                $tc->can_review = in_array($tc->id, $reviewable_ids, true);
            });
    }
}
