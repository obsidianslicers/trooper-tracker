<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Database\Factories\Base\TrooperRequestFactory as BaseTrooperRequestFactory;

class TrooperRequestFactory extends BaseTrooperRequestFactory
{
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TrooperRequest::ORGANIZATION_ID => Organization::factory(),
            TrooperRequest::PRIMARY_ORGANIZATION_ID => Organization::factory(),
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function forPrimaryOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function withIdentifier(string $identifier): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::IDENTIFIER => $identifier,
        ]);
    }

    public function asPending(): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
    }

    public function asApproved(?Trooper $by = null): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::STATUS => TrooperRequestStatus::APPROVED,
            TrooperRequest::APPROVED_BY_ID => $by?->id,
            TrooperRequest::APPROVED_AT => now(),
        ]);
    }

    public function asDenied(?Trooper $by = null, ?string $reason = null): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperRequest::STATUS => TrooperRequestStatus::DENIED,
            TrooperRequest::DENIED_BY_ID => $by?->id,
            TrooperRequest::DENIED_AT => now(),
            TrooperRequest::DENIAL_REASON => $reason,
        ]);
    }
}
