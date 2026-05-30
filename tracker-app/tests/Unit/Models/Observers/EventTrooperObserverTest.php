<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Observers\EventTrooperObserver;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_assigns_costume_organization_ids_when_costume_changes(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $costume->id;

        $subject = new EventTrooperObserver();

        $subject->saving($event_trooper);

        $this->assertSame([
            $organization->id,
        ], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
        $this->assertSame([], $event_trooper->{EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS});
    }

    public function test_saving_assigns_costume_organization_ids_for_handler_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        // Wire handler costume into the approval chain (as the backfill/observer would do)
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($handler_costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $event_trooper->{EventTrooper::COSTUME_ID} = $handler_costume->id;

        $subject = new EventTrooperObserver();
        $subject->saving($event_trooper);

        $this->assertSame([$organization->id], $event_trooper->{EventTrooper::COSTUME_ORGANIZATION_IDS});
    }

    public function test_created_queues_forum_thread_sync_for_event_roster_changes(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_updated_queues_forum_thread_sync_when_status_changes(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::fake();

        $event_trooper->status = 'cancelled';
        $event_trooper->save();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_created_does_not_queue_forum_thread_sync_when_xenforo_is_not_configured(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => null,
            'services.xenforo.api_key' => null,
        ]);

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        Queue::assertNothingPushed();
    }
}