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
            Trooper::NAME => $this->faker->name(),
            Trooper::EMAIL => $this->faker->unique()->email(),
            Trooper::USERNAME => $this->faker->userName(),
            Trooper::PASSWORD => Hash::make(Trooper::PASSWORD),
            Trooper::THEME => $this->faker->word(),
            Trooper::MEMBERSHIP_STATUS => $this->faker->word(),
            Trooper::MEMBERSHIP_ROLE => $this->faker->word(),
            Trooper::INSTANT_NOTIFICATION => $this->faker->randomNumber(1),
            Trooper::ATTENDANCE_NOTIFICATION => $this->faker->randomNumber(1),
            Trooper::COMMAND_STAFF_NOTIFICATION => $this->faker->randomNumber(1),
        ];
    }
}
