<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Database\Factories\Base\EventShiftFactory as BaseEventShiftFactory;

class EventShiftFactory extends BaseEventShiftFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventShift::STATUS => EventStatus::OPEN,
            EventShift::SHIFT_STARTS_AT => $this->faker->dateTimeBetween('now', '+1 hour'),
            EventShift::SHIFT_ENDS_AT => $this->faker->dateTimeBetween('+2 hours', '+3 hours'),
        ]);
    }
}