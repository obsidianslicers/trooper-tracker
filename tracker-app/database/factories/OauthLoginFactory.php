<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OauthLogin;
use App\Models\Trooper;
use Database\Factories\Base\OauthLoginFactory as BaseOauthLoginFactory;

class OauthLoginFactory extends BaseOauthLoginFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            OauthLogin::PROVIDER => 'google',
            OauthLogin::PROVIDER_ID => fake()->uuid(),
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            OauthLogin::TROOPER_ID => $trooper->id,
        ]);
    }

    public function forProvider(string $provider): static
    {
        return $this->state(fn(array $attributes): array => [
            OauthLogin::PROVIDER => $provider,
        ]);
    }
}
