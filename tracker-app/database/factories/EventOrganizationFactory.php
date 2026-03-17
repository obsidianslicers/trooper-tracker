<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Database\Factories\Base\EventOrganizationFactory as BaseEventOrganizationFactory;

class EventOrganizationFactory extends BaseEventOrganizationFactory
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

    public function forEvent(Event $event): static
    {
        return $this->state(fn(array $attributes): array => [
            EventOrganization::EVENT_ID => $event->{Event::ID},
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            EventOrganization::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function canAttend(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventOrganization::CAN_ATTEND => true,
        ]);
    }

    public function cannotAttend(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventOrganization::CAN_ATTEND => false,
        ]);
    }
}
