<?php

namespace Database\Factories\Base;

use App\Models\EventShiftStation;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventShiftStationFactory extends Factory
{
    public function definition(): array
    {
        return [
            EventShiftStation::EVENT_SHIFT_ID => \App\Models\EventShift::factory(),
            EventShiftStation::NAME => $this->faker->words(2, true),
            EventShiftStation::TROOPERS_ALLOWED => $this->faker->numberBetween(1, 6),
            EventShiftStation::SEQUENCE => $this->faker->numberBetween(1, 100),
        ];
    }
}
