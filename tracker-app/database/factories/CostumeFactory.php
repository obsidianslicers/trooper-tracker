<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Costume;
use Database\Factories\Base\CostumeFactory as BaseCostumeFactory;

class CostumeFactory extends BaseCostumeFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return parent::definition();
    }

    public function withName(string $name): static
    {
        return $this->state(fn(array $attributes): array => [
            Costume::NAME => $name,
        ]);
    }
}
