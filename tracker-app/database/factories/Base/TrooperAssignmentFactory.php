<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperAssignment;

class TrooperAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperAssignment::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperAssignment::ORGANIZATION_ID => \App\Models\Organization::factory(),
            TrooperAssignment::CAN_NOTIFY => $this->faker->randomNumber(1),
            TrooperAssignment::IS_MEMBER => $this->faker->randomNumber(1),
            TrooperAssignment::IS_MODERATOR => $this->faker->randomNumber(1),
        ];
    }
}
