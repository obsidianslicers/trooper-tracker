<?php

namespace Database\Factories;

use App\Enums\TrooperEventStatus;
use App\Models\Event;
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
            EventTrooper::STATUS => TrooperEventStatus::NONE,
        ]);
    }

    public function withShift(EventShift $shift): static
    {
        return $this->state(fn(array $attributes) => [
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
        ]);
    }

    public function withTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes) => [
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);
    }
}
