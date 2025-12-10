<?php

namespace Database\Factories;

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
        ]);
    }
}
