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
     * @param  GetTrooperCostumesQuery  $message  The query containing the trooper
     * @return \Illuminate\Support\Collection<int, \App\Models\Costume> Trooper's costumes
     */
    public function __invoke(object $message): mixed
    {
        $costumes = Costume::forTrooper($message->trooper->id, $message->organization_ids)
            ->whereNotIn(Costume::NAME, [Costume::COMMAND_STAFF, Costume::HANDLER])
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

            $costume->costume_organizations = "{$prefix}{$name_list}";
        });

        return $results;
    }
}
