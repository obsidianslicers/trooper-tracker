<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Database\Factories\Base\EventTrooperFactory as BaseEventTrooperFactory;

class EventTrooperFactory extends BaseEventTrooperFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventTrooper::STATUS => EventTrooperStatus::NONE,
        ]);
    }

    public function forEventShift(EventShift $event_shift): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->{EventShift::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::TROOPER_ID => $trooper->{Trooper::ID},
            EventTrooper::ADDED_BY_TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function withCostume(Costume $costume): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::COSTUME_ID => $costume->{Costume::ID},
        ]);
    }

    public function withBackupCostume(Costume $costume): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::BACKUP_COSTUME_ID => $costume->{Costume::ID},
        ]);
    }

    /**
     * @param array<int, int> $organization_ids
     */
    public function withCostumeOrganizationIds(array $organization_ids): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::COSTUME_ORGANIZATION_IDS => $organization_ids,
        ]);
    }

    /**
     * @param array<int, int> $organization_ids
     */
    public function withBackupCostumeOrganizationIds(array $organization_ids): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS => $organization_ids,
        ]);
    }

    public function asGoing(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);
    }

    public function asTentative(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);
    }

    public function asAttended(): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
        ]);
    }

    public function withSignedUpAt(\Carbon\Carbon $date): static
    {
        return $this->state(fn(array $attributes): array => [
            EventTrooper::SIGNED_UP_AT => $date,
        ]);
    }
}
