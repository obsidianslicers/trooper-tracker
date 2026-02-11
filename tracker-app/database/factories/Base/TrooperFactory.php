<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Trooper;

class TrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Trooper::EMAIL => $this->faker->unique()->email(),
            Trooper::PASSWORD => Hash::make(Trooper::PASSWORD),
            Trooper::THEME => $this->faker->word(),
            Trooper::MEMBERSHIP_STATUS => $this->faker->word(),
            Trooper::MEMBERSHIP_ROLE => $this->faker->word(),
            Trooper::NOTIFICATION_FREQUENCY => $this->faker->word(),
        ];
    }
}
