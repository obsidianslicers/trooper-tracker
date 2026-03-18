<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventGuestCommand;
use App\Models\EventGuest;
use Tests\TestCase;

/**
 * @see UpdateEventGuestCommand
 */
class UpdateEventGuestCommandTest extends TestCase
{
    public function test_constructor_stores_event_guest_and_valid_data(): void
    {
        $event_guest = new EventGuest([EventGuest::ID => 42]);
        $valid_data = [EventGuest::STATUS => 'going', EventGuest::NAME => 'Wedge Antilles'];

        $subject = new UpdateEventGuestCommand(
            event_guest: $event_guest,
            valid_data: $valid_data,
        );

        $this->assertSame($event_guest, $subject->event_guest);
        $this->assertSame($valid_data, $subject->valid_data);
    }
}
