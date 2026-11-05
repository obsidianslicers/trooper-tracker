<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Costumes;

use App\Models\Base\Organization;
use App\Models\Costume;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve all costumes owned by a trooper.
 *
 * Returns the trooper's costume collection with related organization_costume
 * and organization data for display purposes.
 *
 * @method static Collection<int, Costume> call(Trooper $trooper, ?array $organization_ids = null)
 */
final class GetTrooperCostumesForDisplay extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper whose costumes to retrieve
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly ?array $organization_ids = null) {}

    /**
     * Execute the query to retrieve trooper costumes.
     *
     * Loads costumes with relationships:
     * - organization_costume: The costume template
     * - organization_costume.organization: The owning organization
     *
     * @return Collection<int, Costume> Trooper's costumes
     */
    public function handle(): Collection
    {
        $costumes = Costume::forTrooper($this->trooper->id, $this->organization_ids)
            ->whereNotIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER])
            ->orderBy(Costume::NAME)
            ->get();

        // Ensure related data is available without N+1 queries
        $costumes->loadMissing(['organization_costumes.organization', 'organization_costumes.trooper_costumes']);

        // Transform for the final output
        $results = $costumes->each(function ($costume)
        {
            $names = $costume->organization_costumes
                ->map(fn ($oc) => $oc->organization->name)
                ->sort()
                ->values();

            $prefix = $names->count() > 1 ? '(*) ' : '';
            $name_list = $names->isEmpty() ? '(unattached)' : $names->implode(', ');

            $costume->costume_organizations = "{$prefix}{$name_list}";

            // Attach all costume image URLs for this trooper when available
            $trooperCostumes = $costume->organization_costumes
                ->flatMap(fn ($oc) => $oc->trooper_costumes)
                ->where('trooper_id', $this->trooper->id);

            $imageUrls = $trooperCostumes
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
                ->values();

            $costume->image_urls = $imageUrls;
        });

        return $results;
    }
}
