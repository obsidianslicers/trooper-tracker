<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventCommand;
use App\Models\Event;
use Tests\TestCase;

/**
 * @see UpdateEventCommand
 */
class UpdateEventCommandTest extends TestCase
{
    public function test_constructor_stores_event_and_data(): void
    {
        $event = new Event([Event::ID => 123]);
        $data = [Event::NAME => 'Updated Event', Event::VENUE => 'New Venue'];

        $subject = new UpdateEventCommand(
            event: $event,
            data: $data
        );

        $this->assertSame($event, $subject->event);
        $this->assertSame($data, $subject->data);
    }
}
