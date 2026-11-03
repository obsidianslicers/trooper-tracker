<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventGuestStatus;
use App\Messages\Events\Commands\UpdateEventGuest;
use App\Models\EventGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventGuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_event_guest_with_valid_data(): void
    {
        $event_guest = EventGuest::factory()->create([
            EventGuest::STATUS => EventGuestStatus::GOING,
            EventGuest::NAME => 'Luke Skywalker',
        ]);

        $command = new UpdateEventGuest(
            event_guest: $event_guest,
            valid_data: [
                EventGuest::STATUS => EventGuestStatus::CANCELLED,
                EventGuest::NAME => 'Han Solo',
            ],
        );

        $command->handle();

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::ID => $event_guest->id,
            EventGuest::STATUS => EventGuestStatus::CANCELLED->value,
            EventGuest::NAME => 'Han Solo',
        ]);
    }

    public function test_handle_persists_status_change_to_database(): void
    {
        $event_guest = EventGuest::factory()->create([
            EventGuest::STATUS => EventGuestStatus::GOING,
        ]);

        $command = new UpdateEventGuest(
            event_guest: $event_guest,
            valid_data: [EventGuest::STATUS => EventGuestStatus::TENTATIVE],
        );

        $command->handle();

        $event_guest->refresh();

        $this->assertSame(EventGuestStatus::TENTATIVE, $event_guest->status);
    }

    public function test_handle_persists_name_change_to_database(): void
    {
        $event_guest = EventGuest::factory()->withName('C-3PO')->create();

        $command = new UpdateEventGuest(
            event_guest: $event_guest,
            valid_data: [EventGuest::NAME => 'R2-D2'],
        );

        $command->handle();

        $event_guest->refresh();

        $this->assertSame('R2-D2', $event_guest->{EventGuest::NAME});
    }

    public function test_handle_returns_null(): void
    {
        $event_guest = EventGuest::factory()->create();

        $command = new UpdateEventGuest(
            event_guest: $event_guest,
            valid_data: [EventGuest::STATUS => EventGuestStatus::GOING],
        );

        $result = $command->handle();

        $this->assertNull($result);
    }
}
