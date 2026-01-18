<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\NotificationFrequency;
use App\Features\Events\Queries\GetTroopersForEventCreatedQuery;
use App\Features\Events\Queries\GetTroopersForEventCreatedQueryHandler;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersForEventCreatedQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_troopers_with_notifications_enabled(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($trooper->id, $result->first()->id);
    }

    public function test_invoke_excludes_troopers_with_notification_frequency_never(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_troopers_without_can_notify(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => false,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_only_returns_active_troopers(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $retired_trooper = Trooper::factory()->asRetired()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $retired_trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_filters_by_organization_id(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization1->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization2->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_empty_collection_when_no_eligible_troopers(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_handles_daily_notification_frequency(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $query = new GetTroopersForEventCreatedQuery($event);
        $subject = new GetTroopersForEventCreatedQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($trooper->id, $result->first()->id);
    }
}