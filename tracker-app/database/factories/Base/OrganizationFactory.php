<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Organization;

class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Organization::PARENT_ID => null,
            Organization::NAME => $this->faker->name(),
            Organization::TYPE => $this->faker->word(),
            Organization::DEPTH => $this->faker->randomNumber(),
            Organization::SEQUENCE => $this->faker->randomNumber(),
            Organization::NODE_PATH => $this->faker->word(),
            Organization::CAN_ATTEND_DEFAULT => $this->faker->randomNumber(1),
            Organization::REQUIRES_GUARDIAN => $this->faker->randomNumber(1),
        ];
    }
}
