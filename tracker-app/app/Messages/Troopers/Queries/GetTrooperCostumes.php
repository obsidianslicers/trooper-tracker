<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Costume;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $trooper)
 */
final class GetTrooperCostumes extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {
    }

    public function handle(): Collection
    {
        $organization_ids = GetTrooperOrganizations::call($this->trooper)
            ->pluck(TrooperOrganization::ORGANIZATION_ID)
            ->all();

        $relations = [
            'organization_costumes.organization',
            'organization_costumes.trooper_costumes'
        ];

        $costumes = Costume::query()
            ->with($relations)
            ->forTrooper($this->trooper->id, $organization_ids)
            ->whereNotIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER])
            ->orderBy(Costume::NAME)
            ->get();

        // Transform for the final output
        $results = $costumes->each(function (Costume $costume)
        {
            $costume->costume_organizations = $this->getOrganizationNames($costume);
            $costume->image_urls = $this->getImageUrls($costume);
        });

        return $results;
    }

    private function getOrganizationNames(Costume $costume): string
    {
        $organization_names = $costume->organization_costumes
            ->map(fn($oc) => $oc->organization->name)
            ->sort()
            ->values();

        $prefix = $organization_names->count() > 1 ? '(*) ' : '';
        $name_list = '';

        if ($organization_names->isEmpty())
        {
            //  bascially in the list but not approved or attached to any organization
            //  (ie) Handler, and Command Staff - can be picked but aren't approved
            //  from the initial migration we may have some unapproved organizations
            //  where the member has a costume, but the relationship is not approved.
            //  or someone subsequently retired
            $name_list = '(inactive membership)';
        }
        else
        {
            $name_list = $organization_names->implode(', ');
        }
        return "{$prefix}{$name_list}";
    }

    private function getImageUrls(Costume $costume): array
    {
        // Attach all costume image URLs for this trooper when available
        $trooper_costumes = $costume->organization_costumes
            ->flatMap(fn($oc) => $oc->trooper_costumes)
            ->where('trooper_id', $this->trooper->id);

        return $trooper_costumes
            ->flatMap(function ($tc)
            {
                return [
                    $tc->image_url_sm,
                    $tc->image_url_lg,
                    $tc->image_url_bucket_off,
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
