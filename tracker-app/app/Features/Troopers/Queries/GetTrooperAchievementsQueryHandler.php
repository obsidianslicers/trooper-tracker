<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\AchievementType;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Support\Collection;

/**
 * Retrieves milestone achievements within a lookback window.
 *
 * @implements QueryHandlerInterface<GetTrooperAchievementsQuery>
 */
readonly class GetTrooperAchievementsQueryHandler implements QueryHandlerInterface
{
    /**
     * Loads milestone achievements with related trooper data.
     *
     * @return Collection<int, TrooperAchievement>
     */
    public function __invoke(object $message): mixed
    {
        $lookback = $message->parseLookback();

        $relations = [
            'organization',
            'trooper:'.implode(',', [Trooper::ID, Trooper::DISPLAY_NAME]),
        ];

        $types = collect(AchievementType::cases())
            ->filter(fn (AchievementType $type) => $type->isMilestone())
            ->map(fn (AchievementType $type) => $type->value)
            ->toArray();

        return TrooperAchievement::with($relations)
            ->where(TrooperAchievement::ACHIEVEMENT_DATE, '>=', $lookback)
            ->whereIn(TrooperAchievement::TYPE, $types)
            ->orderByDesc(TrooperAchievement::ACHIEVEMENT_DATE)
            ->get();
    }
}
