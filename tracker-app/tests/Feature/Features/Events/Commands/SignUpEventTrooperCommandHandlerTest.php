<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Commands\SignUpEventTrooperCommandHandler;
use App\Jobs\CreateTrooperFriendshipJob;
use App\Mail\Events\TrooperSignUp;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * @see SignUpEventTrooperCommandHandler
 */
class SignUpEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_trooper_with_going_status(): void
    {
        Mail::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();
        $added_by = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $added_by
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_sets_added_by_trooper_id_when_different_from_trooper(): void
    {
        Bus::fake();
        Mail::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();
        $added_by = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $added_by
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $added_by->id,
        ]);

        Bus::assertDispatched(CreateTrooperFriendshipJob::class, function (CreateTrooperFriendshipJob $job) use ($trooper, $added_by): bool
        {
            return $job->trooper_id === $added_by->{Trooper::ID}
                && $job->friend_id === $trooper->{Trooper::ID};
        });
    }

    public function test_invoke_sets_added_by_trooper_id_null_when_same_as_trooper(): void
    {
        Bus::fake();
        Mail::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => null,
        ]);

        Bus::assertNotDispatched(CreateTrooperFriendshipJob::class);
    }

    public function test_invoke_queues_sign_up_email(): void
    {
        Mail::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        Mail::assertQueued(TrooperSignUp::class, function ($mail) use ($trooper)
        {
            return $mail->hasTo($trooper->{Trooper::EMAIL});
        });
    }

    public function test_invoke_returns_null(): void
    {
        Mail::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
    }
}
