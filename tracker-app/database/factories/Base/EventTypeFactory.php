<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventType;

class EventTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventType::NAME => $this->faker->name(),
            EventType::SORT_ORDER => $this->faker->word(),
            EventType::IS_COUNTED => $this->faker->randomNumber(1),
            EventType::IS_ACTIVE => $this->faker->randomNumber(1),
        ];
    }
}
