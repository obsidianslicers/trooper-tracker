<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TroopCorrection;

class TroopCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TroopCorrection::TROOPER_ID => \App\Models\Trooper::factory(),
            TroopCorrection::EVENT_ID => \App\Models\Event::factory(),
            TroopCorrection::EVENT_TROOPER_ID => \App\Models\EventTrooper::factory(),
            TroopCorrection::DETAILS => $this->faker->text(),
            TroopCorrection::STATUS => $this->faker->word(),
        ];
    }
}
