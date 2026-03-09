<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trooper;
use App\Models\TrooperDonation;
use Carbon\Carbon;
use Database\Factories\Base\TrooperDonationFactory as BaseTrooperDonationFactory;

class TrooperDonationFactory extends BaseTrooperDonationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperDonation::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function createdAt(Carbon $date): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperDonation::CREATED_AT => $date,
        ]);
    }
}
