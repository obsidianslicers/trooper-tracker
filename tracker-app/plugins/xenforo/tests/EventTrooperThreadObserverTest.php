<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Jobs\UpdateEventThreadJob;

class EventTrooperThreadObserverTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

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

        Queue::assertPushed(UpdateEventThreadJob::class, function (UpdateEventThreadJob $job) use ($event): bool
        {
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

        Queue::assertPushed(UpdateEventThreadJob::class, function (UpdateEventThreadJob $job) use ($event): bool
        {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_created_does_not_queue_forum_thread_sync_when_xenforo_is_not_configured(): void
    {
        Queue::fake();

        config([
            'plugins.xenforo.base_url' => null,
            'plugins.xenforo.api_key' => null,
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
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'test-key',
        ]);
    }

    private function createForumEvent(): Event
    {
        return $this->linkThread(Event::factory()
            ->withOrganization(Organization::factory()->create())
            ->create(), 321, 654);
    }
}
