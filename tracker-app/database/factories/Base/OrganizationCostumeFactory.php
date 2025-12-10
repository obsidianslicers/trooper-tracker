<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\OrganizationCostume;

class OrganizationCostumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            OrganizationCostume::ORGANIZATION_ID => \App\Models\Organization::factory(),
            OrganizationCostume::NAME => $this->faker->name(),
        ];
    }
}
