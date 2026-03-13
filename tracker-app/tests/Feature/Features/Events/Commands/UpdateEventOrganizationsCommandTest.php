<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventOrganizationsCommand;
use App\Models\Event;
use Tests\TestCase;

/**
 * @see UpdateEventOrganizationsCommand
 */
class UpdateEventOrganizationsCommandTest extends TestCase
{
    public function test_constructor_stores_event_and_data(): void
    {
        $event = new Event([Event::ID => 123]);
        $data = [1 => ['can_attend' => true], 2 => ['can_attend' => false]];

        $subject = new UpdateEventOrganizationsCommand(
            event: $event,
            data: $data
        );

        $this->assertSame($event, $subject->event);
        $this->assertSame($data, $subject->data);
    }
}
