<?php

namespace Database\Factories;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Database\Factories\Base\AwardTrooperFactory as BaseAwardTrooperFactory;

class AwardTrooperFactory extends BaseAwardTrooperFactory
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

    public function forAward(Award $award): static
    {
        return $this->state(fn(array $attributes): array => [
            AwardTrooper::AWARD_ID => $award->{Award::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            AwardTrooper::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function onDate(string $award_date): static
    {
        return $this->state(fn(array $attributes): array => [
            AwardTrooper::AWARD_DATE => $award_date,
        ]);
    }
}
