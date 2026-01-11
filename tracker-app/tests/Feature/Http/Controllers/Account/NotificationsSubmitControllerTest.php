<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NotificationsSubmitController.
 *
 * Verifies:
 * - Authenticated troopers can update their notification frequency
 * - Per-organization notification preferences are correctly updated
 * - Existing assignments are properly handled
 * - Success flash message is displayed
 * - Redirects to the correct route
 * - Unauthenticated users are redirected to login
 */
class NotificationsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::DAILY->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals(NotificationFrequency::DAILY, $trooper->notification_frequency);
    }

    public function test_invoke_creates_organization_notification_preferences(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
                'organizations' => [
                    $org1->id => ['can_notify' => true],
                    $org2->id => ['can_notify' => false],
                ],
            ]);

        // Assert
        $assignment1 = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertNotNull($assignment1);
        $this->assertTrue($assignment1->can_notify);

        $assignment2 = TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertNotNull($assignment2);
        $this->assertFalse($assignment2->can_notify);
    }

    public function test_invoke_updates_existing_organization_notification_preferences(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
                'organizations' => [
                    $organization->id => ['can_notify' => true],
                ],
            ]);

        // Assert
        $existing_assignment->refresh();
        $this->assertTrue($existing_assignment->can_notify);
    }

    public function test_invoke_disables_notifications_for_organizations_not_in_request(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org_to_keep = Organization::factory()->create();
        $org_to_disable = Organization::factory()->create();

        // Create two assignments with notifications enabled
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_to_keep->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        $assignment_to_disable = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org_to_disable->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        // Act - only include org_to_keep in the request
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
                'organizations' => [
                    $org_to_keep->id => ['can_notify' => true],
                ],
            ]);

        // Assert - org_to_disable should have notifications disabled
        $assignment_to_disable->refresh();
        $this->assertFalse($assignment_to_disable->can_notify);
    }

    public function test_invoke_handles_empty_organizations_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $organization = Organization::factory()->create();
        $existing_assignment = TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::CAN_NOTIFY => true,
        ]);

        // Act - submit with empty organizations array
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::DAILY->value,
                'organizations' => [],
            ]);

        // Assert - existing assignment should be disabled
        $existing_assignment->refresh();
        $this->assertFalse($existing_assignment->can_notify);
    }

    public function test_invoke_redirects_to_notifications_route(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
            ]);

        // Assert
        $response->assertRedirect(route('account.notifications'));
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
            ]);

        // Assert
        $response->assertSessionHas('flash_messages');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->post(route('account.notifications'), [
            'notification_frequency' => NotificationFrequency::INSTANT->value,
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_validates_notification_frequency_is_required(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                // Missing notification_frequency
            ]);

        // Assert
        $response->assertSessionHasErrors('notification_frequency');
    }

    public function test_invoke_validates_notification_frequency_is_valid_enum(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => 'invalid_value',
            ]);

        // Assert
        $response->assertSessionHasErrors('notification_frequency');
    }

    public function test_invoke_validates_organization_can_notify_is_boolean(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::INSTANT->value,
                'organizations' => [
                    $organization->id => ['can_notify' => 'not_a_boolean'],
                ],
            ]);

        // Assert
        $response->assertSessionHasErrors("organizations.{$organization->id}.can_notify");
    }

    public function test_invoke_updates_all_notification_preferences_atomically(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER,
        ]);
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notifications'), [
                'notification_frequency' => NotificationFrequency::DAILY->value,
                'organizations' => [
                    $org1->id => ['can_notify' => true],
                    $org2->id => ['can_notify' => false],
                ],
            ]);

        // Assert - verify both global and per-org settings were updated
        $trooper->refresh();
        $this->assertEquals(NotificationFrequency::DAILY, $trooper->notification_frequency);

        $this->assertEquals(2, $trooper->trooper_assignments()->count());

        $assignment1 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org1->id)
            ->first();
        $this->assertTrue($assignment1->can_notify);

        $assignment2 = $trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $org2->id)
            ->first();
        $this->assertFalse($assignment2->can_notify);
    }

    public function test_invoke_accepts_all_valid_notification_frequency_values(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Test each valid enum value
        foreach (NotificationFrequency::cases() as $frequency)
        {
            // Act
            $response = $this->actingAs($trooper)
                ->post(route('account.notifications'), [
                    'notification_frequency' => $frequency->value,
                ]);

            // Assert
            $response->assertRedirect(route('account.notifications'));
            $trooper->refresh();
            $this->assertEquals($frequency, $trooper->notification_frequency);
        }
    }
}
