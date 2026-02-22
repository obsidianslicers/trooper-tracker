<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Updated Event',
            Event::EVENT_START => now()->addDays(30)->toDateTimeString(),
            Event::EVENT_END => now()->addDays(30)->addHours(2)->toDateTimeString(),
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::NAME => 'Original Name']);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Updated Event Name',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect(route('admin.events.update', $event));
        $this->assertDatabaseHas(Event::class, [
            Event::ID => $event->id,
            Event::NAME => 'Updated Event Name',
        ]);
    }

    public function test_invoke_redirects_to_update_page(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect(route('admin.events.update', $event));
    }

    public function test_invoke_administrator_can_update_any_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
    }

    public function test_invoke_moderator_can_update_moderated_event(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $event = Event::factory()->create([Event::ORGANIZATION_ID => $org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Moderator Updated',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Event::class, [
            Event::ID => $event->id,
            Event::NAME => 'Moderator Updated',
        ]);
    }

    public function test_invoke_moderator_cannot_update_non_moderated_event(): void
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

        $event = Event::factory()->create([Event::ORGANIZATION_ID => $other_org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Should Not Update',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_event(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Should Not Update',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_dispatches_notification_when_draft_to_open(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::DRAFT]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_dispatches_notification_when_draft_to_sign_up_locked(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::DRAFT]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::SIGN_UP_LOCKED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_does_not_dispatch_notification_when_open_to_sign_up_locked(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::SIGN_UP_LOCKED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        Queue::assertNotPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_invoke_marks_shifts_as_cancelled_when_event_cancelled(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $shift1 = EventShift::factory()->for($event)->create([EventShift::STATUS => EventStatus::OPEN]);
        $shift2 = EventShift::factory()->for($event)->create([EventShift::STATUS => EventStatus::OPEN]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::CANCELLED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(EventShift::class, [
            EventShift::ID => $shift1->id,
            EventShift::STATUS => EventStatus::CANCELLED->value,
        ]);
        $this->assertDatabaseHas(EventShift::class, [
            EventShift::ID => $shift2->id,
            EventShift::STATUS => EventStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_marks_event_troopers_as_cancelled_when_event_cancelled(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $shift = EventShift::factory()->for($event)->create([EventShift::STATUS => EventStatus::OPEN]);
        $event_trooper = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::CANCELLED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_skips_already_cancelled_troopers_when_event_cancelled(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $shift = EventShift::factory()->for($event)->create([EventShift::STATUS => EventStatus::OPEN]);

        $already_cancelled = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper1->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);

        $to_be_cancelled = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper2->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::CANCELLED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        // Already cancelled trooper remains cancelled
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $already_cancelled->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);
        // Approved trooper gets cancelled
        $this->assertDatabaseHas(EventTrooper::class, [
            EventTrooper::ID => $to_be_cancelled->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_dispatches_cancelled_notification_when_event_cancelled(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::CANCELLED->value,
            Event::NAME => $event->name,
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        Queue::assertPushed(SendEventCancelledNotificationsJob::class);
    }

    public function test_invoke_does_not_dispatch_notification_when_status_unchanged(): void
    {
        // Arrange
        Queue::fake();
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.update', $event), [
            Event::STATUS => EventStatus::OPEN->value,
            Event::NAME => 'Updated Name',
            Event::EVENT_START => $event->event_start->toDateTimeString(),
            Event::EVENT_END => $event->event_end->toDateTimeString(),
            Event::VENUE => $event->venue,
            Event::VENUE_ADDRESS => $event->venue_address,
        ]);

        // Assert
        $response->assertRedirect();
        Queue::assertNothingPushed();
    }
}
