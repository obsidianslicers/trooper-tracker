<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Database\Factories\Base\EventShareFactory as BaseEventShareFactory;
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
}
