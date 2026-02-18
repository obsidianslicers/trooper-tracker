<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperApiCode;

class TrooperApiCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperApiCode::TROOPERID => $this->faker->randomNumber(),
            TrooperApiCode::API_CODE => $this->faker->unique()->word(),
            TrooperApiCode::DATE_CREATED => $this->faker->dateTime(),
        ];
    }
}
