<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;

/**
 * Retrieves awarded trooper records within a lookback window.
 *
 * @implements QueryHandlerInterface<GetTrooperAwardsQuery>
 */
readonly class GetTrooperAwardsQueryHandler implements QueryHandlerInterface
{
    /**
     * Loads award records with related award, organization, and trooper data.
     *
     * @return \Illuminate\Support\Collection<int, AwardTrooper>
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $award_columns = [Award::ID, Award::NAME, Award::ORGANIZATION_ID];
        $organization_columns = [Organization::ID, Organization::NAME, Organization::IMAGE_PATH_SM];
        $trooper_columns = [Trooper::ID, Trooper::DISPLAY_NAME];

        $relations = [
            'award:'.implode(',', $award_columns),
            'award.organization:'.implode(',', $organization_columns),
            'trooper:'.implode(',', $trooper_columns),
        ];

        return AwardTrooper::with($relations)
            ->where(AwardTrooper::AWARD_DATE, '>=', $lookback)
            ->orderByDesc(AwardTrooper::AWARD_DATE)
            ->get();
    }
}
