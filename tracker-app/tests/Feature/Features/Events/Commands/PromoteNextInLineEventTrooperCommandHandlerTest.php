<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommand;
use App\Features\Events\Commands\PromoteNextInLineEventTrooperCommandHandler;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\TrooperPromotedToGoingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see PromoteNextInLineEventTrooperCommandHandler
 */
class PromoteNextInLineEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_promotes_next_standby_trooper_to_going(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $cancelled_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::IS_HANDLER => false,
            ]);

        $standby_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => false,
            ]);

        $command = new PromoteNextInLineEventTrooperCommand(event_trooper: $cancelled_trooper);
        $handler = app(PromoteNextInLineEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $standby_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_matches_handler_role(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $cancelled_handler = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::IS_HANDLER => true,
            ]);

        $standby_member = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => false,
            ]);

        $standby_handler = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => true,
            ]);

        $command = new PromoteNextInLineEventTrooperCommand(event_trooper: $cancelled_handler);
        $handler = app(PromoteNextInLineEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $standby_handler->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $standby_member->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_queues_next_in_line_email(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $cancelled_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $standby_trooper_model = Trooper::factory()->create();
        $standby_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($standby_trooper_model)
            ->create([
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => $cancelled_trooper->{EventTrooper::IS_HANDLER},
            ]);

        $command = new PromoteNextInLineEventTrooperCommand(event_trooper: $cancelled_trooper);
        $handler = app(PromoteNextInLineEventTrooperCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($standby_trooper_model, TrooperPromotedToGoingNotification::class);
    }

    public function test_invoke_returns_null_when_no_standby_trooper_available(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $cancelled_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->create([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);

        $command = new PromoteNextInLineEventTrooperCommand(event_trooper: $cancelled_trooper);
        $handler = app(PromoteNextInLineEventTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
        Notification::assertNothingSent();
    }
}
