<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventWatch;
use App\Models\Trooper;
use Database\Factories\Base\EventWatchFactory as BaseEventWatchFactory;

class EventWatchFactory extends BaseEventWatchFactory
{
    public function definition(): array
    {
        return parent::definition();
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes): array => [
            EventWatch::EVENT_ID => $event->{Event::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventWatch::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }
}
