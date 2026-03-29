<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\MobileDevice;

class MobileDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            MobileDevice::TROOPER_ID => \App\Models\Trooper::factory(),
            MobileDevice::FCM_TOKEN => $this->faker->unique()->sha1(),
        ];
    }
}
