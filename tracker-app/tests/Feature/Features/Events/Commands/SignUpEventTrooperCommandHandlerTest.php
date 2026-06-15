<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\SignUpEventTrooperCommand;
use App\Features\Events\Commands\SignUpEventTrooperCommandHandler;
use App\Jobs\CreateTrooperFriendshipJob;
use App\Mail\Events\TrooperSignUp;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Notifications\Events\TrooperSignedUpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see SignUpEventTrooperCommandHandler
 */
class SignUpEventTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_trooper_with_going_status(): void
    {
        Notification::fake();

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
        Notification::fake();

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
        Notification::fake();

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
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        Notification::assertSentTo($trooper, TrooperSignedUpNotification::class);
    }

    public function test_invoke_returns_null(): void
    {
        Notification::fake();

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

    public function test_invoke_saves_organization_id_when_provided(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->create();

        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $organization->id
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_saves_selected_costume_and_credit_orgs(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $trooper = Trooper::factory()->create();
        $costume = Costume::factory()->create();
        $org_costume = OrganizationCostume::factory()->forOrganization($organization)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $handler = app(SignUpEventTrooperCommandHandler::class);
        $handler(new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            costume_id: $costume->id,
        ));

        $event_trooper = EventTrooper::query()->where(EventTrooper::TROOPER_ID, $trooper->id)->firstOrFail();

        $this->assertSame($costume->id, $event_trooper->costume_id);
        $this->assertSame([$organization->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_sets_is_handler_from_selected_handler_costume(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();
        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        $handler = app(SignUpEventTrooperCommandHandler::class);
        $handler(new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            costume_id: $handler_costume->id,
        ));

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $handler_costume->id,
            EventTrooper::IS_HANDLER => true,
        ]);
    }

    public function test_invoke_ignores_missing_costume_id(): void
    {
        Notification::fake();

        $event_shift = EventShift::factory()->create();
        $trooper = Trooper::factory()->create();

        $handler = app(SignUpEventTrooperCommandHandler::class);
        $handler(new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            costume_id: 999999,
        ));

        $event_trooper = EventTrooper::query()->where(EventTrooper::TROOPER_ID, $trooper->id)->firstOrFail();

        $this->assertNull($event_trooper->costume_id);
        $this->assertNull($event_trooper->costume_organization_ids);
    }

    public function test_invoke_assigns_stand_by_when_org_limit_reached(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        // Fill the org slot
        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $organization->id])
            ->create();

        $trooper = Trooper::factory()->create();
        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $organization->id
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_assigns_going_when_org_limit_not_reached(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 2,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $organization->id])
            ->create();

        $trooper = Trooper::factory()->create();
        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: $organization->id
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_ignores_org_limit_when_no_organization_id(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $organization->id])
            ->create();

        $trooper = Trooper::factory()->create();
        $command = new SignUpEventTrooperCommand(
            event_shift: $event_shift,
            trooper: $trooper,
            added_by_trooper: $trooper,
            organization_id: null
        );
        $handler = app(SignUpEventTrooperCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }
}
