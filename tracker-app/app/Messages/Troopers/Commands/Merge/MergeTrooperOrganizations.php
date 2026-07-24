<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;

/**
 * Merges the organizations of two troopers.
 * This command ensures that all organizational affiliations of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 * 
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 * 
 */
final class MergeTrooperOrganizations extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_organizations = TrooperOrganization::query()
            ->withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperOrganization::ID)
            ->get();

        foreach ($source_organizations as $source_organization)
        {
            $target_organization = $this->getTargetOrganization($source_organization);

            if ($target_organization === null)
            {
                //  not found in target, create a new association for the target trooper
                $source_organization->trooper_id = $this->target_trooper->id;
                $source_organization->save();

                continue;
            }

            $this->mergeOrganizations($target_organization, $source_organization);
        }
    }

    private function getTargetOrganization(TrooperOrganization $source_organization): ?TrooperOrganization
    {
        return TrooperOrganization::query()
            ->withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $this->target_trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $source_organization->organization_id)
            ->first();
    }

    private function mergeOrganizations(TrooperOrganization $target_organization, TrooperOrganization $source_organization): void
    {
        if ($target_organization->trashed() && !$source_organization->trashed())
        {
            $target_organization->restore();
        }

        $source_organization->identifier = null;
        $source_organization->save();

        if (empty($target_organization->identifier) && !empty($source_organization->identifier))
        {
            $target_organization->identifier = $source_organization->identifier;
            $target_organization->save();
        }
    }
}