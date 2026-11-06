<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\DateConcerns;
use App\Models\Costume;
use App\Models\OrganizationCostume;
use Hyperdrive\Message;

/**
 * Moves the source costume's club links onto the target costume.
 *
 * A club that only carries the source gets its link repointed. A club that carries both
 * keeps the target's link, takes over the source link's troopers, and the source link is
 * permanently deleted.
 *
 * @method static void call(Costume $target_costume, Costume $source_costume)
 */
final class MergeOrganizationCostumes extends Message
{
    use DateConcerns;

    public function __construct(
        private readonly Costume $target_costume,
        private readonly Costume $source_costume,
    ) {}

    public function handle(): void
    {
        $source_organization_costumes = OrganizationCostume::query()
            ->withTrashed()
            ->where(OrganizationCostume::COSTUME_ID, $this->source_costume->id)
            ->orderBy(OrganizationCostume::ID)
            ->get();

        foreach ($source_organization_costumes as $source_organization_costume)
        {
            $target_organization_costume = $this->getTargetOrganizationCostume(
                $source_organization_costume,
            );

            if ($target_organization_costume === null)
            {
                $source_organization_costume->costume_id = $this->target_costume->id;
                $source_organization_costume->save();

                continue;
            }

            $this->mergeOrganizationCostumes(
                $target_organization_costume,
                $source_organization_costume,
            );
        }
    }

    private function getTargetOrganizationCostume(
        OrganizationCostume $source_organization_costume,
    ): ?OrganizationCostume {
        return OrganizationCostume::query()
            ->withTrashed()
            ->where(OrganizationCostume::COSTUME_ID, $this->target_costume->id)
            ->where(
                OrganizationCostume::ORGANIZATION_ID,
                $source_organization_costume->organization_id,
            )
            ->first();
    }

    private function mergeOrganizationCostumes(
        OrganizationCostume $target_organization_costume,
        OrganizationCostume $source_organization_costume,
    ): void {
        if ($target_organization_costume->trashed() && !$source_organization_costume->trashed())
        {
            $target_organization_costume->restore();
        }

        MergeOrganizationCostumeTroopers::call(
            target_organization_costume: $target_organization_costume,
            source_organization_costume: $source_organization_costume,
        );

        $target_organization_costume->prefix ??= $source_organization_costume->prefix;
        $target_organization_costume->synchronized_at = $this->latestDateTime(
            $target_organization_costume->synchronized_at,
            $source_organization_costume->synchronized_at,
        );
        $target_organization_costume->save();

        $source_organization_costume->forceDelete();
    }
}
