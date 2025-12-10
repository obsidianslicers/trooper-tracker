<?php

namespace Database\Factories;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\Organization;
use Database\Factories\Base\AwardFactory as BaseAwardFactory;

class AwardFactory extends BaseAwardFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            Award::FREQUENCY => AwardFrequency::ONCE
        ]);
    }

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes) => [
            Award::ORGANIZATION_ID => $organization,
        ]);
    }
}