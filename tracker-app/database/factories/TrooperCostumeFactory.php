<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Database\Factories\Base\TrooperCostumeFactory as BaseTrooperCostumeFactory;

class TrooperCostumeFactory extends BaseTrooperCostumeFactory
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
            TrooperCostume::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function forOrganizationCostume(OrganizationCostume $organization_costume): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->{OrganizationCostume::ID},
        ]);
    }
}
