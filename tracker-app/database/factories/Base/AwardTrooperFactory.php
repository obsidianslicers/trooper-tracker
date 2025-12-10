<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\AwardTrooper;

class AwardTrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            AwardTrooper::AWARD_ID => \App\Models\Award::factory(),
            AwardTrooper::TROOPER_ID => \App\Models\Trooper::factory(),
            AwardTrooper::AWARD_DATE => $this->faker->dateTime(),
        ];
    }
}
