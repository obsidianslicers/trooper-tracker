<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Base\Organization;
use App\Models\Costume;

/**
 * Handler for retrieving a trooper's costume collection.
 *
 * Returns all TrooperCostume records for the specified trooper,
 * eagerly loading related organization_costume and organization data
 * for efficient display rendering.
 *
 * @implements QueryHandlerInterface<GetTrooperCostumesQuery>
 */
readonly class GetTrooperCostumesQueryHandler implements QueryHandlerInterface
{
    /**
     * Execute the query to retrieve trooper costumes.
     *
     * Loads costumes with relationships:
     * - organization_costume: The costume template
     * - organization_costume.organization: The owning organization
     *
     * @param GetTrooperCostumesQuery $message The query containing the trooper
     * @return \Illuminate\Support\Collection<int, \App\Models\Costume> Trooper's costumes
     */
    public function __invoke(object $message): mixed
    {
        $trooper_id = $message->trooper->id;

        $costumes = Costume::query()
            ->whereHas('organization_costumes.trooper_costumes', function ($query) use ($trooper_id)
            {
                $query->where('trooper_id', $trooper_id);
            })
            ->with(['organization_costumes' => function ($query) use ($trooper_id)
            {
                // Only pull the organization details if the trooper actually has the approval
                $with = 'organization:' . Organization::ID . ',' . Organization::NAME;
                $query->with($with)
                    ->whereHas('trooper_costumes', function ($q) use ($trooper_id)
                    {
                        $q->where('trooper_id', $trooper_id);
                    });
            }])
            ->orderBy(Costume::NAME)
            ->get();

        // Transform for the final output
        $results = $costumes->each(function ($costume)
        {
            $names = $costume->organization_costumes
                ->map(fn($oc) => $oc->organization->name)
                ->sort()
                ->values();

            $prefix = $names->count() > 1 ? '(*) ' : '';
            $name_list = $names->isEmpty() ? '(unattached)' : $names->implode(', ');

            $costume->display_organizations = "{$prefix}{$name_list}";
        });

        return $results;
    }
}