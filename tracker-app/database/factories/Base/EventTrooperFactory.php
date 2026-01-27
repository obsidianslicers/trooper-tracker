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
            EventTrooper::BACKUP_COSTUME_ID => \App\Models\OrganizationCostume::factory(),
            EventTrooper::ADDED_BY_TROOPER_ID => \App\Models\Trooper::factory(),
            EventTrooper::TROOPER_ID => \App\Models\Trooper::factory(),
            EventTrooper::COSTUME_ID => \App\Models\OrganizationCostume::factory(),
            EventTrooper::EVENT_SHIFT_ID => \App\Models\EventShift::factory(),
        ];
    }
}
