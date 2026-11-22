<?php

declare(strict_types=1);

namespace App\Messages\Awards\Commands;

use App\Events\AwardRevoked;
use App\Models\Award;
use App\Models\AwardTrooper;
use Hyperdrive\Message;

/**
 * Takes one grant of an award back. Returns the trooper's name, or null when the grant
 * isn't one of this award's.
 *
 * @method static string|null call(Award $award, int $award_trooper_id)
 */
final class RevokeTrooperAward extends Message
{
    public function __construct(
        private readonly Award $award,
        private readonly int $award_trooper_id,
    ) {}

    public function handle(): ?string
    {
        $award_trooper = AwardTrooper::query()
            ->with('trooper:id,display_name')
            ->where(AwardTrooper::ID, $this->award_trooper_id)
            ->where(AwardTrooper::AWARD_ID, $this->award->id)
            ->first();

        if ($award_trooper === null)
        {
            return null;
        }

        $award_trooper->delete();

        AwardRevoked::dispatch($award_trooper);

        return $award_trooper->trooper?->display_name ?? '';
    }
}
