<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving clubs available for a trooper to request membership in.
 *
 * @implements QueryHandlerInterface<GetAvailableClubsQuery>
 */
readonly class GetAvailableClubsQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetAvailableClubsQuery  $message
     * @return Collection<int, Organization>
     */
    public function __invoke(object $message): mixed
    {
        $trooper_id = $message->trooper->id;

        // Exclude families where the trooper already has an active membership.
        // Pending requests are still shown — submitting a new one in the same family replaces it.
        $active_org_ids = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID);

        $excluded_roots = collect();

        if ($active_org_ids->isNotEmpty())
        {
            Organization::whereIn(Organization::ID, $active_org_ids)
                ->get([Organization::NODE_PATH])
                ->each(function ($org) use (&$excluded_roots)
                {
                    $root_id = explode(Organization::NODE_PATH_SEP, $org->node_path)[0];

                    if ($root_id !== '')
                    {
                        $excluded_roots->push($root_id);
                    }
                });

            $excluded_roots = $excluded_roots->unique()->values();
        }

        $query = Organization::query();

        foreach ($excluded_roots as $root_id)
        {
            // LIKE '1:%' matches "1:", "1:5:", "1:5:12:" — the entire family
            $query->where(Organization::NODE_PATH, 'NOT LIKE', $root_id . Organization::NODE_PATH_SEP . '%');
        }

        return $query->orderBy(Organization::SEQUENCE)->get();
    }
}
