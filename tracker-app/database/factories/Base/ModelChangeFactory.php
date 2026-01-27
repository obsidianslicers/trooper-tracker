<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\ModelChange;

class ModelChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ModelChange::AUDITABLE_TYPE => $this->faker->word(),
            ModelChange::AUDITABLE_ID => $this->faker->randomDigitNotNull(),
            ModelChange::TROOPER_ID => \App\Models\Trooper::factory(),
            ModelChange::FIELD_NAME => $this->faker->word(),
        ];
    }
}
