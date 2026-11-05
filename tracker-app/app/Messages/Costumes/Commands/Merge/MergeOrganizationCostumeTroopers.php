<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\DateConcerns;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Moves the troopers approved for one club costume onto another club costume.
 *
 * A trooper who only has the source gets their record repointed. A trooper who has both
 * keeps the target record (filling in any missing photos from the source), any display
 * costume pointing at the source moves to the target, and the source record is
 * permanently deleted.
 *
 * @method static void call(
 *     OrganizationCostume $target_organization_costume,
 *     OrganizationCostume $source_organization_costume,
 * )
 */
final class MergeOrganizationCostumeTroopers extends Message
{
    use DateConcerns;

    public function __construct(
        private readonly OrganizationCostume $target_organization_costume,
        private readonly OrganizationCostume $source_organization_costume,
    ) {}

    public function handle(): void
    {
        $source_trooper_costumes = TrooperCostume::query()
            ->withTrashed()
            ->where(
                TrooperCostume::ORGANIZATION_COSTUME_ID,
                $this->source_organization_costume->id,
            )
            ->orderBy(TrooperCostume::ID)
            ->get();

        foreach ($source_trooper_costumes as $source_trooper_costume)
        {
            $target_trooper_costume = $this->getTargetTrooperCostume($source_trooper_costume);

            if ($target_trooper_costume === null)
            {
                $source_trooper_costume->organization_costume_id =
                    $this->target_organization_costume->id;
                $source_trooper_costume->save();

                continue;
            }

            $this->mergeTrooperCostumes($target_trooper_costume, $source_trooper_costume);
        }
    }

    private function getTargetTrooperCostume(TrooperCostume $source_trooper_costume): ?TrooperCostume
    {
        return TrooperCostume::query()
            ->withTrashed()
            ->where(TrooperCostume::TROOPER_ID, $source_trooper_costume->trooper_id)
            ->where(
                TrooperCostume::ORGANIZATION_COSTUME_ID,
                $this->target_organization_costume->id,
            )
            ->first();
    }

    private function mergeTrooperCostumes(
        TrooperCostume $target_trooper_costume,
        TrooperCostume $source_trooper_costume,
    ): void {
        if ($target_trooper_costume->trashed() && !$source_trooper_costume->trashed())
        {
            $target_trooper_costume->restore();
        }

        $target_trooper_costume->image_url_sm ??= $source_trooper_costume->image_url_sm;
        $target_trooper_costume->image_url_lg ??= $source_trooper_costume->image_url_lg;
        $target_trooper_costume->image_url_bucket_off ??=
            $source_trooper_costume->image_url_bucket_off;
        $target_trooper_costume->synchronized_at = $this->latestDateTime(
            $target_trooper_costume->synchronized_at,
            $source_trooper_costume->synchronized_at,
        );
        $target_trooper_costume->save();

        Trooper::withTrashed()
            ->where(Trooper::DISPLAY_COSTUME_ID, $source_trooper_costume->id)
            ->update([Trooper::DISPLAY_COSTUME_ID => $target_trooper_costume->id]);

        $source_trooper_costume->forceDelete();
    }
}
