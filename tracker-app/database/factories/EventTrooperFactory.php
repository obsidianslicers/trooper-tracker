<?php

namespace Database\Factories;

use App\Enums\EventTrooperStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Database\Factories\Base\EventTrooperFactory as BaseEventTrooperFactory;

class EventTrooperFactory extends BaseEventTrooperFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventTrooper::STATUS => EventTrooperStatus::NONE,
        ]);
    }
}
