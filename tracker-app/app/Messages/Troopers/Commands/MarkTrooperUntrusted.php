<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * An administrator flags a trooper as "do not trust". From then on their troop
 * confirmations wait for a moderator. Optionally, costumes they added themselves go
 * back to officer review.
 */
final class MarkTrooperUntrusted extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Actor $actor,
        private readonly Trooper $trooper,
        private readonly string $reason,
        private readonly bool $review_costumes = false,
    ) {}

    public function handle(): void
    {
        $this->trooper->is_untrusted = true;
        $this->trooper->untrusted_reason = $this->reason;
        $this->trooper->untrusted_at = now();
        $this->trooper->untrusted_id = $this->actor->id;
        $this->trooper->save();

        if ($this->review_costumes)
        {
            $this->sendSelfAddedCostumesBackToReview();
        }
    }

    private function sendSelfAddedCostumesBackToReview(): void
    {
        TrooperCostume::query()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->whereNull(TrooperCostume::SYNCHRONIZED_AT)
            ->get()
            ->each(function (TrooperCostume $trooper_costume): void
            {
                $trooper_costume->markPending();
                $trooper_costume->save();
            });
    }
}
