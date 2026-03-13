<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
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

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShift::EVENT_ID => $event->{Event::ID},
        ]);
    }

    public function withShiftStartsAt(\Carbon\Carbon $date): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShift::SHIFT_STARTS_AT => $date,
        ]);
    }

    public function withShiftEndsAt(\Carbon\Carbon $date): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShift::SHIFT_ENDS_AT => $date,
        ]);
    }

    public function asClosed(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShift::STATUS => EventStatus::CLOSED,
        ]);
    }
}