<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventShift;

class EventShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventShift::EVENT_ID => \App\Models\Event::factory(),
            EventShift::STATUS => $this->faker->word(),
            EventShift::SHIFT_STARTS_AT => $this->faker->dateTime(),
            EventShift::SHIFT_ENDS_AT => $this->faker->dateTime(),
        ];
    }
}
