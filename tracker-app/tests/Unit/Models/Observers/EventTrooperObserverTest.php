<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_queues_forum_thread_sync_for_event_roster_changes(): void
    {
        Queue::fake();
        $this->configureXenforo();

        $event = $this->createForumEvent();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->create())
            ->create();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_updated_queues_forum_thread_sync_when_status_changes(): void
    {
        Queue::fake();
        $this->configureXenforo();

        $event = $this->createForumEvent();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->create())
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

        $event = $this->createForumEvent();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->create())
            ->create();

        Queue::assertNothingPushed();
    }

    private function configureXenforo(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);
    }

    private function createForumEvent(): Event
    {
        return Event::factory()
            ->withOrganization(Organization::factory()->create())
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);
    }
}
