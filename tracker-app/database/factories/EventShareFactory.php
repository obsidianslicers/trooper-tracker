<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Database\Factories\Base\EventShareFactory as BaseEventShareFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EventShareFactory extends BaseEventShareFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = Event::factory()->create();

        return array_merge(parent::definition(), [
            EventShare::EVENT_ID => $event->id,
            EventShare::TROOPER_ID => Trooper::factory(),
            EventShare::SHARE_TOKEN => Str::uuid()->toString(),
            EventShare::RECIPIENT_EMAIL => $this->faker->safeEmail(),
            EventShare::VIEW_COUNT => 0,
            EventShare::EXPIRES_AT => $event->event_end->addDay(),
            EventShare::IS_REVOKED => false,
        ]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShare::EVENT_ID => $event->{Event::ID},
            EventShare::EXPIRES_AT => $event->event_end?->addDay() ?? now()->addDay(),
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShare::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function expiresAt(Carbon $expires_at): static
    {
        return $this->state(fn(array $attributes): array => [
            EventShare::EXPIRES_AT => $expires_at,
        ]);
    }
}
