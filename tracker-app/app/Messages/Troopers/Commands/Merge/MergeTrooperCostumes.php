<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Merges the costumes of two troopers.
 * This command ensures that all costumes of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTrooperCostumes extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $source_costumes = TrooperCostume::query()
            ->withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperCostume::ID)
            ->get();

        foreach ($source_costumes as $source_costume)
        {
            $target_costume = $this->getTargetCostume($source_costume);

            if ($target_costume === null)
            {
                $source_costume->trooper_id = $this->target_trooper->id;
                $source_costume->save();

                continue;
            }

            $this->mergeCostumes($target_costume, $source_costume);
        }
    }

    private function getTargetCostume(TrooperCostume $source_costume): ?TrooperCostume
    {
        return TrooperCostume::query()
            ->withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $this->target_trooper->id)
            ->where(
                TrooperCostume::ORGANIZATION_COSTUME_ID,
                $source_costume->organization_costume_id,
            )
            ->first();
    }

    private function mergeCostumes(TrooperCostume $target_costume, TrooperCostume $source_costume): void
    {
        if ($target_costume->trashed() && !$source_costume->trashed())
        {
            $target_costume->restore();
        }

        $target_costume->image_url_sm = $source_costume->image_url_sm;
        $target_costume->image_url_lg = $source_costume->image_url_lg;
        $target_costume->image_url_bucket_off = $source_costume->image_url_bucket_off;

        $target_costume->save();
        $source_costume->forceDelete();
    }
}
