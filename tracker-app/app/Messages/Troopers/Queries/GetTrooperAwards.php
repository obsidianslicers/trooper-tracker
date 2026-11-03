<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Messages\Concerns\HasLookback;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Defines criteria for retrieving awarded trooper records.
 *
 * Supports lookback filtering by days (int), date string, or Carbon instance.
 *
 * @method static Collection<int, AwardTrooper> call(int|string|Carbon $lookback)
 */
final class GetTrooperAwards extends Message
{
    use HasLookback;

    /**
     * Initializes the awards query.
     *
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly int|string|Carbon $lookback,
    ) {}

    /**
     * Loads award records with related award, organization, and trooper data.
     *
     * @return Collection<int, AwardTrooper>
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

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
