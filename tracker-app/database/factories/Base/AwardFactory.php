<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Award;

class AwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Award::ORGANIZATION_ID => \App\Models\Organization::factory(),
            Award::NAME => $this->faker->unique()->name(),
            Award::FREQUENCY => $this->faker->word(),
            Award::HAS_MULTIPLE_RECIPIENTS => $this->faker->randomNumber(1),
        ];
    }
}
