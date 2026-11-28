<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries\Corrections;

use App\Models\TroopCorrection;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * A trooper's requests for troop credit, newest first, with the event and shift each was for.
 *
 * @method static Collection<int, TroopCorrection> call(int $trooper_id)
 */
final class GetTroopCorrectionsForTrooper extends Message
{
    public function __construct(private readonly int $trooper_id) {}

    /**
     * @return Collection<int, TroopCorrection>
     */
    public function handle(): Collection
    {
        return TroopCorrection::query()
            ->where(TroopCorrection::TROOPER_ID, $this->trooper_id)
            ->with(['event', 'event_trooper.event_shift'])
            ->orderByDesc(TroopCorrection::CREATED_AT)
            ->orderByDesc(TroopCorrection::ID)
            ->get();
    }
}
