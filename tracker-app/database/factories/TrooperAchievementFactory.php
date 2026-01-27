<?php

namespace Database\Factories;

use App\Enums\AchievementType;
use App\Models\TrooperAchievement;
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
}
