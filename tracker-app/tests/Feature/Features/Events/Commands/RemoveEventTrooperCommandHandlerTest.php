<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\RemoveEventTrooperCommand;
use App\Features\Events\Commands\RemoveEventTrooperCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see RemoveEventTrooperCommandHandler
 */
class RemoveEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private RemoveEventTrooperCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = app(RemoveEventTrooperCommandHandler::class);
    }

    public function test_invoke_soft_deletes_event_trooper(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->asMember()->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create();

        ($this->subject)(new RemoveEventTrooperCommand($event_trooper));

        $this->assertSoftDeleted('tt_event_troopers', [
            EventTrooper::ID => $event_trooper->id,
        ]);
    }
}
