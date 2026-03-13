<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trooper;
use App\Models\TrooperApiCode;
use Database\Factories\Base\TrooperApiCodeFactory as BaseTrooperApiCodeFactory;

class TrooperApiCodeFactory extends BaseTrooperApiCodeFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TrooperApiCode::TROOPERID => Trooper::factory(),
            TrooperApiCode::API_CODE => fake()->uuid(),
            TrooperApiCode::DATE_CREATED => now(),
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperApiCode::TROOPERID => $trooper->{Trooper::ID},
        ]);
    }

    public function withApiCode(string $api_code): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperApiCode::API_CODE => $api_code,
        ]);
    }
}
