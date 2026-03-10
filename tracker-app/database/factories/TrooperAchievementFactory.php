<?php

namespace Database\Factories;

use App\Enums\AchievementType;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Database\Factories\Base\TrooperAchievementFactory as BaseTrooperAchievementFactory;

class TrooperAchievementFactory extends BaseTrooperAchievementFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function withType(AchievementType $type): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::TYPE => $type,
        ]);
    }

    public function earnedOn(Carbon $earned_on): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::EARNED_ON => $earned_on,
        ]);
    }
}
