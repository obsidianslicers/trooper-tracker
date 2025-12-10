<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperCostume;

class TrooperCostumeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperCostume::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperCostume::COSTUME_ID => \App\Models\OrganizationCostume::factory(),
        ];
    }
}
