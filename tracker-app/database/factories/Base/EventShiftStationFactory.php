<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventShiftStation;

class EventShiftStationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventShiftStation::EVENT_SHIFT_ID => \App\Models\EventShift::factory(),
            EventShiftStation::NAME => $this->faker->name(),
            EventShiftStation::TROOPERS_ALLOWED => $this->faker->randomNumber(),
            EventShiftStation::SEQUENCE => $this->faker->randomNumber(),
        ];
    }
}
