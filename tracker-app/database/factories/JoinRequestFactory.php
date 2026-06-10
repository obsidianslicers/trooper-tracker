<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JoinRequestStatus;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Database\Factories\Base\JoinRequestFactory as BaseJoinRequestFactory;

class JoinRequestFactory extends BaseJoinRequestFactory
{
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            JoinRequest::ORGANIZATION_ID         => Organization::factory(),
            JoinRequest::PRIMARY_ORGANIZATION_ID => Organization::factory(),
            JoinRequest::STATUS                  => JoinRequestStatus::PENDING,
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function forPrimaryOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function withIdentifier(string $identifier): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::IDENTIFIER => $identifier,
        ]);
    }

    public function asPending(): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::STATUS => JoinRequestStatus::PENDING,
        ]);
    }

    public function asApproved(?Trooper $by = null): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::STATUS          => JoinRequestStatus::APPROVED,
            JoinRequest::APPROVED_BY_ID  => $by?->id,
            JoinRequest::APPROVED_AT     => now(),
        ]);
    }

    public function asDenied(?Trooper $by = null, ?string $reason = null): static
    {
        return $this->state(fn(array $attributes): array => [
            JoinRequest::STATUS         => JoinRequestStatus::DENIED,
            JoinRequest::DENIED_BY_ID   => $by?->id,
            JoinRequest::DENIED_AT      => now(),
            JoinRequest::DENIAL_REASON  => $reason,
        ]);
    }
}
