<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventShift;
use App\Models\EventShiftStation;
use Database\Factories\Base\EventShiftStationFactory as BaseEventShiftStationFactory;

class EventShiftStationFactory extends BaseEventShiftStationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventShiftStation::TROOPERS_ALLOWED => $this->faker->numberBetween(1, 10),
            EventShiftStation::SEQUENCE => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function forEventShift(EventShift $event_shift): static
    {
        return $this->state(fn (array $attributes): array => [
            EventShiftStation::EVENT_SHIFT_ID => $event_shift->id,
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            EventShiftStation::NAME => $name,
        ]);
    }

    public function withTroopersAllowed(int $troopers_allowed): static
    {
        return $this->state(fn (array $attributes): array => [
            EventShiftStation::TROOPERS_ALLOWED => $troopers_allowed,
        ]);
    }
}
