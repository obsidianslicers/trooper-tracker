<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventNotification;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Database\Factories\Base\EventNotificationFactory as BaseEventNotificationFactory;

class EventNotificationFactory extends BaseEventNotificationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), []);
    }
}