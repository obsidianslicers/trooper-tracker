<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\UpdateEventRoster;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use App\Services\EventRosterCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UpdateEventRosterTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private EventShift $shift;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->event = Event::factory()->create([Event::TROOPERS_ALLOWED => null]);
        $this->shift = EventShift::factory()->forEvent($this->event)->create();
        $this->admin = Trooper::factory()->asSystemOwner()->create();
    }

    public function test_handle_updates_guest_status_and_ignores_unknown_or_blank_guests(): void
    {
        $guest = EventGuest::factory()->forEventShift($this->shift)->asGoing()->create();
        $untouched = EventGuest::factory()->forEventShift($this->shift)->asGoing()->create();

        $this->update(guests: [
            $guest->id => ['status' => EventGuestStatus::CANCELLED->value],
            $untouched->id => ['status' => null],
            999999 => ['status' => EventGuestStatus::CANCELLED->value],
        ]);

        $this->assertEquals(EventGuestStatus::CANCELLED->value, $guest->fresh()->getRawOriginal('status'));
        $this->assertEquals(EventGuestStatus::GOING->value, $untouched->fresh()->getRawOriginal('status'));
    }

    public function test_handle_ignores_unknown_troopers_and_blank_statuses(): void
    {
        $event_trooper = $this->signup(EventTrooperStatus::GOING);

        $this->update(troopers: [
            $event_trooper->id => ['status' => null],
            999999 => ['status' => EventTrooperStatus::CANCELLED->value],
        ]);

        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
    }

    public function test_handle_notifies_trooper_selected_from_standby_on_manual_selection_event(): void
    {
        $this->event->update([Event::STATUS => EventStatus::MANUAL_SELECTION]);
        $event_trooper = $this->signup(EventTrooperStatus::STAND_BY);

        $this->update(troopers: [
            $event_trooper->id => ['status' => EventTrooperStatus::GOING->value],
        ]);

        $this->assertSame(EventTrooperStatus::GOING, $event_trooper->fresh()->status);
        Notification::assertSentTo($event_trooper->trooper, ManualSelectionApprovedNotification::class);
        Notification::assertNotSentTo($event_trooper->trooper, ManualSelectionStandByNotification::class);
    }

    public function test_handle_notifies_trooper_moved_to_standby_on_manual_selection_event(): void
    {
        $this->event->update([Event::STATUS => EventStatus::MANUAL_SELECTION]);
        $event_trooper = $this->signup(EventTrooperStatus::GOING);

        $this->update(troopers: [
            $event_trooper->id => ['status' => EventTrooperStatus::STAND_BY->value],
        ]);

        Notification::assertSentTo($event_trooper->trooper, ManualSelectionStandByNotification::class);
    }

    public function test_handle_sends_no_selection_notices_outside_manual_selection_events(): void
    {
        $event_trooper = $this->signup(EventTrooperStatus::STAND_BY);

        $this->update(troopers: [
            $event_trooper->id => ['status' => EventTrooperStatus::GOING->value],
        ]);

        Notification::assertNothingSent();
    }

    public function test_handle_applies_a_new_costume_to_an_attended_row(): void
    {
        $event_trooper = $this->signup(EventTrooperStatus::ATTENDED);
        $new_costume = Costume::factory()->create();

        $this->update(troopers: [
            $event_trooper->id => [
                'status' => EventTrooperStatus::ATTENDED->value,
                'costume_id' => (string) $new_costume->id,
                'organization_ids' => [],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame($new_costume->id, $event_trooper->costume_id);
        $this->assertNull($event_trooper->organization_id);
    }

    private function signup(EventTrooperStatus $status): EventTrooper
    {
        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper(Trooper::factory()->create())
            ->withCostume(Costume::factory()->create())
            ->state([EventTrooper::STATUS => $status])
            ->create();
    }

    private function update(array $troopers = [], array $guests = []): void
    {
        (new UpdateEventRoster($this->event, $this->admin, $troopers, $guests, null))
            ->handle(new EventRosterCapacityService);
    }
}
