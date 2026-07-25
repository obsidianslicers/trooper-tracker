<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventTrooper;

class EventTrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventTrooper::EVENT_SHIFT_ID => \App\Models\EventShift::factory(),
            EventTrooper::EVENT_SHIFT_STATION_ID => \App\Models\EventShiftStation::factory(),
            EventTrooper::TROOPER_ID => \App\Models\Trooper::factory(),
            EventTrooper::ORGANIZATION_ID => \App\Models\Organization::factory(),
            EventTrooper::COSTUME_ID => \App\Models\Costume::factory(),
            EventTrooper::IS_ATTENDING_WITHOUT_COSTUME => $this->faker->randomNumber(1),
            EventTrooper::BACKUP_COSTUME_ID => \App\Models\Costume::factory(),
            EventTrooper::ADDED_BY_TROOPER_ID => \App\Models\Trooper::factory(),
            EventTrooper::IS_HANDLER => $this->faker->randomNumber(1),
            EventTrooper::STATUS => $this->faker->word(),
            EventTrooper::SIGNED_UP_AT => $this->faker->dateTime(),
        ];
    }
}
