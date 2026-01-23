<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\Events\GetTroopersForEventCreatedNotificationQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the GetTroopersForEventCreatedNotificationQuery service.
 *
 * Validates that the service correctly retrieves troopers eligible
 * for event creation notifications based on their preferences and assignments.
 */
class GetTroopersForEventCreatedNotificationQueryTest extends TestCase
{
    use RefreshDatabase;

    private GetTroopersForEventCreatedNotificationQuery $subject;
    private Event $event;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GetTroopersForEventCreatedNotificationQuery();
        $this->organization = Organization::factory()->create();
        $this->event = Event::factory()->withOrganization($this->organization)->create();
    }

    public function test_invoke_returns_active_trooper_with_instant_notification_and_can_notify(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($trooper));
    }

    public function test_invoke_returns_trooper_with_daily_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($trooper));
    }

    public function test_invoke_does_not_return_trooper_with_never_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_does_not_return_trooper_without_can_notify_permission(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $this->organization->id,
            'can_notify' => false,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_does_not_return_inactive_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::PENDING,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_does_not_return_trooper_assigned_to_different_organization(): void
    {
        // Arrange
        $different_org = Organization::factory()->create();

        $trooper = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper->id,
            'organization_id' => $different_org->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_empty_collection_when_no_eligible_troopers(): void
    {
        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_multiple_eligible_troopers(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        $trooper2 = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::DAILY,
        ]);

        $trooper3 = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper1->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper2->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper3->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($trooper1));
        $this->assertTrue($result->contains($trooper2));
        $this->assertTrue($result->contains($trooper3));
    }

    public function test_invoke_filters_out_ineligible_troopers_from_mixed_group(): void
    {
        // Arrange
        $eligible = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        $never_notify = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::NEVER,
        ]);

        $no_permission = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        $inactive = Trooper::factory()->create([
            'membership_status' => MembershipStatus::DENIED,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $eligible->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $never_notify->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $no_permission->id,
            'organization_id' => $this->organization->id,
            'can_notify' => false,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $inactive->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($eligible));
        $this->assertFalse($result->contains($never_notify));
        $this->assertFalse($result->contains($no_permission));
        $this->assertFalse($result->contains($inactive));
    }

    public function test_invoke_requires_both_active_status_and_notification_permission(): void
    {
        // Arrange - Active trooper without permission
        $trooper1 = Trooper::factory()->create([
            'membership_status' => MembershipStatus::ACTIVE,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper1->id,
            'organization_id' => $this->organization->id,
            'can_notify' => false,
        ]);

        // Arrange - Inactive trooper with permission
        $trooper2 = Trooper::factory()->create([
            'membership_status' => MembershipStatus::RETIRED,
            'notification_frequency' => NotificationFrequency::INSTANT,
        ]);

        TrooperAssignment::factory()->create([
            'trooper_id' => $trooper2->id,
            'organization_id' => $this->organization->id,
            'can_notify' => true,
        ]);

        // Act
        $result = ($this->subject)($this->event);

        // Assert
        $this->assertCount(0, $result);
    }
}
