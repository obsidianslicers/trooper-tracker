<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\EventType;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_validation_errors_for_invalid_payload(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/update', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/update', []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_flows_status_to_shifts_when_transitioning_from_draft_to_open(): void
    {
        Queue::fake([SendEventCreatedNotificationsJob::class]);

        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::DRAFT])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::DRAFT])
            ->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/update', [
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::OPEN->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'create_forum_thread' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => $shift->id,
            'status' => EventStatus::OPEN->value,
        ]);
    }

    public function test_invoke_cancels_shifts_when_event_is_cancelled(): void
    {
        Queue::fake([SendEventCancelledNotificationsJob::class]);

        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/update', [
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::CANCELLED->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'create_forum_thread' => false,
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_cancels_active_event_troopers_when_event_is_cancelled(): void
    {
        Queue::fake([SendEventCancelledNotificationsJob::class]);

        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->asGoing()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/update', [
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::CANCELLED->value,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
            'create_forum_thread' => false,
        ]);

        $response->assertRedirect();
    }
}
