<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperAchievement;

class TrooperAchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperAchievement::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperAchievement::ORGANIZATION_ID => \App\Models\Organization::factory(),
            TrooperAchievement::TYPE => $this->faker->word(),
        ];
    }
}
