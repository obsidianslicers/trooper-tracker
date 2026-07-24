<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\AwardTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges the awards of two troopers.
 * This command ensures that all award history of the source trooper
 * is transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeAwardTroopers extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_awards = AwardTrooper::query()
            ->withTrashed()
            ->where(AwardTrooper::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(AwardTrooper::ID)
            ->get();

        foreach ($source_awards as $source_award)
        {
            /** @var AwardTrooper $source_award */
            $target_award = $this->getTargetAward($source_award);

            if ($target_award === null)
            {
                $source_award->trooper_id = $this->target_trooper->id;
                $source_award->save();

                continue;
            }

            $this->mergeAwards($target_award, $source_award);
        }
    }

    private function getTargetAward(AwardTrooper $source_award): ?AwardTrooper
    {
        return AwardTrooper::query()
            ->withTrashed()
            ->where(AwardTrooper::TROOPER_ID, $this->target_trooper->id)
            ->where(AwardTrooper::AWARD_ID, $source_award->award_id)
            ->where(AwardTrooper::AWARD_DATE, $source_award->award_date)
            ->first();
    }

    private function mergeAwards(AwardTrooper $target_award, AwardTrooper $source_award): void
    {
        if ($target_award->trashed() && !$source_award->trashed())
        {
            $target_award->restore();
        }

        $source_award->forceDelete();
    }
}
