<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Database\Factories\Base\TrooperFactory as BaseTrooperFactory;
use Illuminate\Support\Facades\Hash;

class TrooperFactory extends BaseTrooperFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->firstName() . ' ' . $this->faker->lastName();

        return array_merge(parent::definition(), [
            Trooper::EMAIL => $this->faker->safeEmail(),
            Trooper::DISPLAY_NAME => $name,
            Trooper::LEGAL_NAME => $name,
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::THEME => TrooperTheme::STORMTROOPER,
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);
    }

    public function asAdministrator(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::ADMINISTRATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function asModerator(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MODERATOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function asMember(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function withEmail(string $email): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::EMAIL => $email,
        ]);
    }

    public function withInvalidEmail(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::EMAIL => fake()->uuid() . 'invalid-email',
        ]);
    }

    public function asPending(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            Trooper::SETUP_COMPLETED_AT => null,
        ]);
    }

    public function withDisplayName(string $display_name): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::DISPLAY_NAME => $display_name,
        ]);
    }

    public function withLegalName(string $legal_name): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::LEGAL_NAME => $legal_name,
        ]);
    }

    public function withNotificationFrequency(NotificationFrequency $frequency): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::NOTIFICATION_FREQUENCY => $frequency,
        ]);
    }

    public function withSetupCompleted(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);
    }

    public function withSetupIncomplete(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::SETUP_COMPLETED_AT => null,
        ]);
    }

    public function asActive(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function asRetired(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::RETIRED,
        ]);
    }

    public function withVerifiedEmail(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::EMAIL_VERIFIED_AT => now(),
        ]);
    }

    public function withUnverifiedEmail(): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::EMAIL_VERIFIED_AT => null,
        ]);
    }

    public function withPassword(string $password): static
    {
        return $this->state(fn(array $attributes): array => [
            Trooper::PASSWORD => Hash::make($password),
        ]);
    }

}
