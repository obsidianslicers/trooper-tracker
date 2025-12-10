<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Event;

class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Event::ORGANIZATION_ID => \App\Models\Organization::factory(),
            Event::NAME => $this->faker->name(),
            Event::TYPE => $this->faker->word(),
            Event::STATUS => $this->faker->word(),
            Event::HAS_ORGANIZATION_LIMITS => $this->faker->randomNumber(1),
            Event::CHARITY_DIRECT_FUNDS => $this->faker->randomNumber(),
            Event::CHARITY_INDIRECT_FUNDS => $this->faker->randomNumber(),
            Event::SECURE_STAGING_AREA => $this->faker->randomNumber(1),
            Event::ALLOW_BLASTERS => $this->faker->randomNumber(1),
            Event::ALLOW_PROPS => $this->faker->randomNumber(1),
            Event::PARKING_AVAILABLE => $this->faker->randomNumber(1),
            Event::ACCESSIBLE => $this->faker->randomNumber(1),
        ];
    }
}
