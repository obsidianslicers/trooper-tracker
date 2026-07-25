<?php

namespace Database\Factories;

use App\Enums\AchievementType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Database\Factories\Base\TrooperAchievementFactory as BaseTrooperAchievementFactory;

class TrooperAchievementFactory extends BaseTrooperAchievementFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
            TrooperAchievement::ORGANIZATION_ID => null,
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function withType(AchievementType $type): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::TYPE => $type,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function earnedOn(Carbon $achievement_date): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAchievement::ACHIEVEMENT_DATE => $achievement_date,
        ]);
    }
}
