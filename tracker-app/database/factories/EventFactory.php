<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Database\Factories\Base\EventFactory as BaseEventFactory;

class EventFactory extends BaseEventFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts_at = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $ends_at = (clone $starts_at)->modify('+4 hours');

        return array_merge(parent::definition(), [
            Event::NAME => $this->faker->sentence(4),
            Event::ORGANIZATION_ID => Organization::factory(),
            Event::EVENT_START => $starts_at,
            Event::EVENT_END => $ends_at,
            Event::TROOPERS_ALLOWED => $this->faker->numberBetween(10, 50),
            Event::HANDLERS_ALLOWED => $this->faker->numberBetween(2, 5),
            Event::TYPE => EventType::REGULAR->value,
            Event::STATUS => EventStatus::OPEN,

            Event::CHARITY_DIRECT_FUNDS => $this->faker->numberBetween(0, 5000),
            Event::CHARITY_INDIRECT_FUNDS => $this->faker->numberBetween(0, 5000),
            Event::CHARITY_NAME => $this->faker->company(),
            Event::CHARITY_HOURS => $this->faker->numberBetween(0, 40),

            Event::LATITUDE => $this->faker->latitude(),
            Event::LONGITUDE => $this->faker->longitude(),

            Event::CONTACT_NAME => $this->faker->name(),
            Event::CONTACT_PHONE => $this->faker->phoneNumber(),
            Event::CONTACT_EMAIL => $this->faker->safeEmail(),

            Event::VENUE => $this->faker->company(),
            Event::VENUE_ADDRESS => $this->faker->streetAddress(),
            Event::VENUE_CITY => $this->faker->city(),
            Event::VENUE_STATE => $this->faker->stateAbbr(),
            Event::VENUE_ZIP => $this->faker->postcode(),
            Event::VENUE_COUNTRY => $this->faker->country(),

            Event::EVENT_WEBSITE => $this->faker->url(),

            Event::EXPECTED_ATTENDEES => $this->faker->numberBetween(10, 500),
            Event::REQUESTED_NUMBER_CHARACTERS => $this->faker->numberBetween(1, 20),
            Event::REQUESTED_CHARACTER_TYPES => $this->faker->randomElement([
                'Stormtroopers, Jedi, Mandalorians',
                'Rebels, Sith Lords',
                'Droids, Other'
            ]),

            Event::SECURE_STAGING_AREA => $this->faker->boolean(),
            Event::ALLOW_BLASTERS => $this->faker->boolean(),
            Event::ALLOW_PROPS => $this->faker->boolean(),
            Event::PARKING_AVAILABLE => $this->faker->boolean(),
            Event::ACCESSIBLE => $this->faker->boolean(),
            Event::AMENITIES => $this->faker->sentence(6),

            Event::COMMENTS => $this->faker->paragraph(),
            Event::REFERRED_BY => $this->faker->name(),
            Event::SOURCE => $this->faker->sentence(5),
        ]);
    }

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::ORGANIZATION_ID => $organization->{Organization::ID},
            Event::PRIMARY_ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function withCreatedByTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::CREATED_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function withCreateNotificationsSent(): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::CREATE_NOTIFICATIONS_SENT_AT => now(),
        ]);
    }

    public function withCancelNotificationsSent(): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::CANCEL_NOTIFICATIONS_SENT_AT => now(),
        ]);
    }

    public function withForumThreadEnabled(): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::CREATE_FORUM_THREAD => true,
        ]);
    }

    public function withForumThreadDisabled(): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::CREATE_FORUM_THREAD => false,
        ]);
    }

    public function withForumThreadId(int $thread_id): static
    {
        return $this->state(fn(array $attributes): array => [
            Event::THREAD_ID => $thread_id,
        ]);
    }
}