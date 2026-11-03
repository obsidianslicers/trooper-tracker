<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\AchievementType;
use App\Messages\Concerns\HasLookback;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Defines criteria for retrieving trooper achievements.
 *
 * Supports lookback filtering by days (int), date string, or Carbon instance.
 *
 * @method static Collection<int, TrooperAchievement> call(int|string|Carbon $lookback)
 */
final class GetTrooperAchievements extends Message
{
    use HasLookback;

    /**
     * Initializes the achievements query.
     *
     * @param  int|string|Carbon  $lookback  Days to look back (int), date string, or Carbon date.
     */
    public function __construct(
        private readonly int|string|Carbon $lookback,
    ) {}

    /**
     * Loads milestone achievements with related trooper data.
     *
     * @return Collection<int, TrooperAchievement>
     */
    public function handle(): Collection
    {
        $lookback = $this->parseLookback();

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
