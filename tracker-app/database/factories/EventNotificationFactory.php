<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventNotification;
use App\Models\Trooper;
use Database\Factories\Base\EventNotificationFactory as BaseEventNotificationFactory;

class EventNotificationFactory extends BaseEventNotificationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), []);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes): array => [
            EventNotification::EVENT_ID => $event->{Event::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventNotification::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }
}