<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Bus\MagicBus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Features\Events\Queries\GetEventShiftDisplayQuery;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AttendanceUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->asClosed()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $response = $this->post(route('events.attendance-update-htmx', ['event_trooper' => $event_trooper->id]), [
            EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_attendance_and_renders_shift_container(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->asClosed()->create();
        EventShift::factory()->forEvent($event)->asClosed()->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($event_shift, $event_trooper, $trooper): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (UpdateEventTrooperCommand $command) use ($event_trooper): bool
                {
                    return $command->event_trooper->id === $event_trooper->id
                        && $command->valid_data === [EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value];
                });

            $mock->shouldReceive('send')
                ->once()
                ->withArgs(function (GetEventShiftDisplayQuery $query) use ($event_shift, $trooper): bool
                {
                    return $query->event_shift->id === $event_shift->id
                        && $query->trooper->id === $trooper->id;
                })
                ->andReturn($event_shift);
        });

        $response = $this->actingAs($trooper)->post(
            route('events.attendance-update-htmx', ['event_trooper' => $event_trooper->id]),
            [EventTrooper::STATUS => EventTrooperStatus::ATTENDED->value]
        );

        $response->assertOk();
        $response->assertViewIs('pages.events.inc.shift-container');
        $response->assertViewHas('open', true);
        $response->assertViewHas('can_moderate', false);
        $response->assertViewHas('count_of_shifts', 2);
        $response->assertViewHas('event_shift', function (EventShift $returned_event_shift) use ($event_shift): bool
        {
            return $returned_event_shift->id === $event_shift->id;
        });
        $response->assertViewHas('event', function (Event $returned_event) use ($organization): bool
        {
            return $returned_event->organization_id === $organization->id
                && $returned_event->event_shifts()->count() === 2;
        });
    }
}