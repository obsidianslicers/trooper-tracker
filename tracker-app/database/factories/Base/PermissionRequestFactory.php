<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\PermissionRequest;

class PermissionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            PermissionRequest::TROOPER_ID => \App\Models\Trooper::factory(),
            PermissionRequest::STATUS => $this->faker->word(),
        ];
    }
}
