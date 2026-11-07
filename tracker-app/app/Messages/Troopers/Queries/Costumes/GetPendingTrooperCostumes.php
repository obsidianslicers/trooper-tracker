<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Costumes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Self-added costumes waiting for review that the given moderator may approve.
 */
final class GetPendingTrooperCostumes extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
    ) {}

    /**
     * @return Collection<int, TrooperCostume>
     */
    public function handle(): Collection
    {
        return TrooperCostume::onlyPending()
            ->moderatedBy($this->moderator)
            ->whereHas('trooper')
            ->with([
                'trooper',
                'organization_costume.costume',
                'organization_costume.organization',
            ])
            ->orderBy(TrooperCostume::CREATED_AT)
            ->get();
    }
}
