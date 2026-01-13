<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for Admin Events CreateSubmitController.
 *
 * Verifies:
 * - Administrators can create events
 * - Moderators can create events for organizations they moderate
 * - Event is created with submitted data
 * - EventOrganization record is created for source club
 * - EventShift is created matching event times
 * - Additional organization associations are created
 * - Notification job is dispatched for OPEN events
 * - Notification job is dispatched for SIGN_UP_LOCKED events
 * - Notification job is not dispatched for DRAFT events
 * - Redirects to the event's update page
 * - Authentication is required
 * - Success message is displayed
 */
class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.events.create'), [
            'organization_id' => $organization->id,
            Event::NAME => 'New Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_administrator_can_create_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'New Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'New Event',
            Event::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_moderator_can_create_event_for_moderated_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Moderated Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($moderator)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'Moderated Event',
        ]);
    }

    public function test_invoke_creates_event_with_submitted_data(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $start = Carbon::parse('2026-02-15 10:00:00');
        $end = Carbon::parse('2026-02-15 14:00:00');

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Convention Appearance',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::VENUE => 'Convention Center',
            Event::VENUE_CITY => 'Springfield',
            Event::CONTACT_NAME => 'John Doe',
            Event::EVENT_START => $start->toDateTimeString(),
            Event::EVENT_END => $end->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
            Event::SECURE_STAGING_AREA => true,
            Event::ALLOW_BLASTERS => true,
            Event::ALLOW_PROPS => true,
            Event::PARKING_AVAILABLE => true,
            Event::ACCESSIBLE => true,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $this->assertDatabaseHas('tt_events', [
            Event::NAME => 'Convention Appearance',
            Event::VENUE => 'Convention Center',
            Event::VENUE_CITY => 'Springfield',
            Event::CONTACT_NAME => 'John Doe',
        ]);
    }

    public function test_invoke_creates_event_organization_for_source_club(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $club = Organization::factory()->create(); // ORGANIZATION type (club)
        $garrison = Organization::factory()->region()->create([
            Organization::PARENT_ID => $club->id,
        ]);

        $event_data = [
            'organization_id' => $garrison->id,
            Event::NAME => 'Garrison Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where(Event::NAME, 'Garrison Event')->first();

        // EventOrganization is created for the source club
        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $club->id,
        ]);

        // Since no organizations array was provided, UpdateEventOrganizationsCommand
        // sets all organizations to can_attend = false
        $event_org = EventOrganization::where(EventOrganization::EVENT_ID, $event->id)
            ->where(EventOrganization::ORGANIZATION_ID, $club->id)
            ->first();
        $this->assertFalse($event_org->can_attend);
    }

    public function test_invoke_creates_event_shift_matching_event_times(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $start = Carbon::parse('2026-02-15 10:00:00');
        $end = Carbon::parse('2026-02-15 14:00:00');

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'New Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => $start->toDateTimeString(),
            Event::EVENT_END => $end->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where(Event::NAME, 'New Event')->first();

        $shift = EventShift::where(EventShift::EVENT_ID, $event->id)->first();
        $this->assertNotNull($shift);
        $this->assertTrue($start->eq($shift->shift_starts_at));
        $this->assertTrue($end->eq($shift->shift_ends_at));
    }

    public function test_invoke_creates_additional_organization_associations(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Multi-Org Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
            'organizations' => [
                $org1->id => [
                    EventOrganization::CAN_ATTEND => true,
                    EventOrganization::TROOPERS_ALLOWED => 10,
                ],
                $org2->id => [
                    EventOrganization::CAN_ATTEND => true,
                    EventOrganization::TROOPERS_ALLOWED => 5,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where(Event::NAME, 'Multi-Org Event')->first();

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::TROOPERS_ALLOWED => 5,
        ]);
    }

    public function test_invoke_dispatches_notification_job_for_open_events(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Open Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_dispatches_notification_job_for_sign_up_locked_events(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Locked Event',
            Event::STATUS => EventStatus::SIGN_UP_LOCKED->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_does_not_dispatch_notification_job_for_draft_events(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'Draft Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        Queue::assertNotPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_redirects_to_event_update_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'New Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $event = Event::where(Event::NAME, 'New Event')->first();
        $response->assertRedirect(route('admin.events.update', ['event' => $event]));
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $event_data = [
            'organization_id' => $organization->id,
            Event::NAME => 'New Event',
            Event::STATUS => EventStatus::DRAFT->value,
            Event::EVENT_START => now()->toDateTimeString(),
            Event::EVENT_END => now()->addHours(2)->toDateTimeString(),
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->post(route('admin.events.create'), $event_data);

        // Assert
        $response->assertSessionHas('flash_messages');
    }
}

