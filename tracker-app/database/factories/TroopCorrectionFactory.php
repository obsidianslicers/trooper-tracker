<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TroopCorrectionStatus;
use App\Models\EventTrooper;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use Database\Factories\Base\TroopCorrectionFactory as BaseTroopCorrectionFactory;

class TroopCorrectionFactory extends BaseTroopCorrectionFactory
{
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TroopCorrection::STATUS => TroopCorrectionStatus::AWAITING_COORDINATOR,
        ]);
    }

    /**
     * The request behind a sign-up: same trooper and event.
     */
    public function forEventTrooper(EventTrooper $event_trooper): static
    {
        return $this->state(fn (array $attributes): array => [
            TroopCorrection::EVENT_TROOPER_ID => $event_trooper->id,
            TroopCorrection::TROOPER_ID => $event_trooper->trooper_id,
            TroopCorrection::EVENT_ID => $event_trooper->event_shift->event_id,
        ]);
    }

    public function forCoordinator(Trooper $coordinator): static
    {
        return $this->state(fn (array $attributes): array => [
            TroopCorrection::COORDINATOR_TROOPER_ID => $coordinator->id,
        ]);
    }

    public function asAwaitingModerator(): static
    {
        return $this->state(fn (array $attributes): array => [
            TroopCorrection::STATUS => TroopCorrectionStatus::AWAITING_MODERATOR,
            TroopCorrection::ESCALATED_AT => now(),
        ]);
    }
}
