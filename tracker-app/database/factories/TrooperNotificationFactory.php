<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trooper;
use App\Models\TrooperNotification;
use Database\Factories\Base\TrooperNotificationFactory as BaseTrooperNotificationFactory;
use Illuminate\Support\Str;

class TrooperNotificationFactory extends BaseTrooperNotificationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TrooperNotification::ID => Str::uuid(),
            TrooperNotification::TYPE => 'test-notification',
            TrooperNotification::NOTIFIABLE_TYPE => Trooper::class,
            TrooperNotification::DATA => ['title' => 'Test notification'],
            TrooperNotification::READ_AT => null,
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn (array $attributes): array => [
            TrooperNotification::NOTIFIABLE_ID => $trooper->{Trooper::ID},
            TrooperNotification::NOTIFIABLE_TYPE => Trooper::class,
        ]);
    }

    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes): array => [
            TrooperNotification::DATA => $data,
        ]);
    }

    public function asRead(): static
    {
        return $this->state(fn (array $attributes): array => [
            TrooperNotification::READ_AT => now(),
        ]);
    }

    public function asUnread(): static
    {
        return $this->state(fn (array $attributes): array => [
            TrooperNotification::READ_AT => null,
        ]);
    }
}
