<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Jobs\DeleteEventThreadJob;
use TroopTracker\Plugins\Xenforo\Jobs\UpdateEventThreadJob;
use TroopTracker\Plugins\Xenforo\Observers\EventThreadObserver;

class EventThreadObserverTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_updated_queues_forum_thread_sync_when_thread_visible_fields_change(): void
    {
        Queue::fake();

        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'test-key',
        ]);

        $organization = Organization::factory()->create();
        $event = $this->linkThread(Event::factory()
            ->withOrganization($organization)
            ->create(), 321, 654);

        $event->name = 'Updated Event Name';
        $event->save();

        Queue::assertPushed(UpdateEventThreadJob::class, function (UpdateEventThreadJob $job) use ($event): bool
        {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_updated_does_not_queue_forum_thread_sync_when_only_thread_identifiers_change(): void
    {
        Queue::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->create();

        $this->linkThread($event, 321, 654);

        Queue::assertNothingPushed();
    }

    public function test_updated_does_not_queue_forum_thread_sync_when_xenforo_is_not_configured(): void
    {
        Queue::fake();

        config([
            'plugins.xenforo.base_url' => null,
            'plugins.xenforo.api_key' => null,
        ]);

        $organization = Organization::factory()->create();
        $event = $this->linkThread(Event::factory()
            ->withOrganization($organization)
            ->create(), 321, 654);

        $event->name = 'Updated Event Name';
        $event->save();

        Queue::assertNotPushed(UpdateEventThreadJob::class);
    }

    public function test_deleted_deletes_forum_thread_when_xenforo_is_configured_and_thread_exists(): void
    {
        Queue::fake();

        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'test-key',
        ]);

        $organization = Organization::factory()->create();
        $event = $this->linkThread(Event::factory()
            ->withOrganization($organization)
            ->create(), 321, 654);

        $subject = new EventThreadObserver;

        $subject->deleted($event);

        Queue::assertPushed(DeleteEventThreadJob::class);
    }
}
