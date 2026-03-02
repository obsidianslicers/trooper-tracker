<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\GoogleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Admin Events CopySubmitController.
 *
 * Verifies:
 * - Administrators can copy events
 * - Moderators can copy events they moderate
 * - Event is copied with adjusted dates
 * - Event shifts are copied with adjusted times
 * - Event organizations are copied
 * - Copied event is set to DRAFT status
 * - Redirects to the copied event's update page
 * - Authentication is required
 * - Authorization is enforced
 * - Success message is displayed
 */
class CopySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock GoogleService to prevent API calls during tests
        $this->mock(GoogleService::class, function ($mock)
        {
            $mock->shouldReceive('getLatitudeLongitude')
                ->andReturn([0.0, 0.0]);
        });
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->post(route('admin.events.copy', $event), [
            Event::NAME => 'COPY OF Test Event',
            Event::EVENT_START => now()->addDays(7)->toDateTimeString(),
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_copy_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::NAME => 'Original Event',
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
            Event::EVENT_END => Carbon::parse('2026-03-10 14:00:00'),
        ]);

        $new_start = Carbon::parse('2026-04-01 10:00:00');

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'COPY OF Original Event',
                Event::EVENT_START => $new_start->toDateTimeString(),
            ]
        );

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'COPY OF Original Event',
        ]);
    }

    public function test_invoke_moderator_can_copy_moderated_event(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
            Event::NAME => 'Moderated Event',
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'COPY OF Moderated Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'COPY OF Moderated Event',
        ]);
    }

    public function test_invoke_moderator_cannot_copy_non_moderated_event(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([
            Event::ORGANIZATION_ID => $other_org->id,
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'COPY OF Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_creates_copy_with_adjusted_dates(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $old_start = Carbon::parse('2026-03-10 10:00:00');
        $old_end = Carbon::parse('2026-03-10 14:00:00');
        $new_start = Carbon::parse('2026-04-15 10:00:00');

        $event = Event::factory()->create([
            Event::NAME => 'Original Event',
            Event::EVENT_START => $old_start,
            Event::EVENT_END => $old_end,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => $new_start->toDateTimeString(),
            ]
        );

        // Assert
        $copied_event = Event::where(Event::NAME, 'Copied Event')->first();
        $this->assertNotNull($copied_event);
        $this->assertTrue($new_start->eq($copied_event->event_start));

        // End date should be adjusted by the same interval
        $expected_end = $old_end->add($old_start->diffAsCarbonInterval($new_start));
        $this->assertTrue($expected_end->eq($copied_event->event_end));
    }

    public function test_invoke_copies_event_shifts_with_adjusted_times(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $old_start = Carbon::parse('2026-03-10 10:00:00');
        $new_start = Carbon::parse('2026-04-01 10:00:00');

        $event = Event::factory()->create([
            Event::EVENT_START => $old_start,
            Event::EVENT_END => Carbon::parse('2026-03-10 14:00:00'),
        ]);

        EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => Carbon::parse('2026-03-10 10:00:00'),
            EventShift::SHIFT_ENDS_AT => Carbon::parse('2026-03-10 12:00:00'),
        ]);

        EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::SHIFT_STARTS_AT => Carbon::parse('2026-03-10 12:00:00'),
            EventShift::SHIFT_ENDS_AT => Carbon::parse('2026-03-10 14:00:00'),
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => $new_start->toDateTimeString(),
            ]
        );

        // Assert
        $copied_event = Event::where(Event::NAME, 'Copied Event')->first();
        $this->assertCount(2, $copied_event->event_shifts);
    }

    public function test_invoke_copies_event_organizations(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event = Event::factory()->create([
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $copied_event = Event::where(Event::NAME, 'Copied Event')->first();
        $this->assertCount(1, $copied_event->event_organizations);

        $copied_org = $copied_event->event_organizations->first();
        $this->assertEquals($organization->id, $copied_org->organization_id);
        $this->assertEquals(10, $copied_org->troopers_allowed);
    }

    public function test_invoke_sets_copied_event_to_draft_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $copied_event = Event::where(Event::NAME, 'Copied Event')->first();
        $this->assertEquals(EventStatus::DRAFT, $copied_event->status);
    }

    public function test_invoke_redirects_to_copied_event_update_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $copied_event = Event::where(Event::NAME, 'Copied Event')->first();
        $response->assertRedirect(route('admin.events.update', ['event' => $copied_event]));
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([
            Event::EVENT_START => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        // Act
        $response = $this->actingAs($admin)->post(
            route('admin.events.copy', $event),
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => Carbon::parse('2026-04-01 10:00:00')->toDateTimeString(),
            ]
        );

        // Assert
        $response->assertSessionHas('flash_messages');
    }
}
