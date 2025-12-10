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
            TrooperAchievement::TROOPED_ALL_SQUADS => $this->faker->randomNumber(1),
            TrooperAchievement::FIRST_TROOP_COMPLETED => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_10 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_25 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_50 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_75 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_100 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_150 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_200 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_250 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_300 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_400 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_500 => $this->faker->randomNumber(1),
            TrooperAchievement::TROOPED_501 => $this->faker->randomNumber(1),
            TrooperAchievement::VOLUNTEER_HOURS => $this->faker->randomNumber(),
            TrooperAchievement::DIRECT_FUNDS => $this->faker->randomNumber(),
            TrooperAchievement::INDIRECT_FUNDS => $this->faker->randomNumber(),
        ];
    }
}
