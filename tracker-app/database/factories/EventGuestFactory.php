<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Database\Factories\Base\EventGuestFactory as BaseEventGuestFactory;

class EventGuestFactory extends BaseEventGuestFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventGuest::IS_HANDLER => false,
            EventGuest::STATUS => 'going',
        ]);
    }

    public function forEventShift(EventShift $event_shift): static
    {
        return $this->state(fn(array $attributes): array => [
            EventGuest::EVENT_SHIFT_ID => $event_shift->{EventShift::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventGuest::ADDED_BY_TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function asHandler(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventGuest::IS_HANDLER => true,
        ]);
    }
}
