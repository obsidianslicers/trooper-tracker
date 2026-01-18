<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;

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
     * @return \Illuminate\Support\Collection<int, \App\Models\TrooperCostume> Trooper's costumes
     */
    public function __invoke(object $message): mixed
    {
        /** @var GetTrooperCostumesQuery $message */

        return $message->trooper->trooper_costumes()->with('organization_costume.organization')->get();
    }
}