<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Jobs\DeleteEventForumThreadJob;
use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Event;
use App\Models\Observers\EventObserver;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_sets_primary_organization_to_top_level_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();

        $subject = new EventObserver();
        $event = Event::factory()->withOrganization($region)->make();

        $subject->creating($event);

        $this->assertSame($club->id, $event->primary_organization_id);
    }

    public function test_updated_queues_forum_thread_sync_when_thread_visible_fields_change(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);

        $event->name = 'Updated Event Name';
        $event->save();

        Queue::assertPushed(UpdateEventForumThreadJob::class, function (UpdateEventForumThreadJob $job) use ($event): bool {
            return $job->uniqueId() === 'forum-thread-sync:event:'.$event->id;
        });
    }

    public function test_updated_does_not_queue_forum_thread_sync_when_only_thread_identifiers_change(): void
    {
        Queue::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->create();

        $event->thread_id = 321;
        $event->post_id = 654;
        $event->save();

        Queue::assertNothingPushed();
    }

    public function test_updated_does_not_queue_forum_thread_sync_when_xenforo_is_not_configured(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => null,
            'services.xenforo.api_key' => null,
        ]);

        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);

        $event->name = 'Updated Event Name';
        $event->save();

        Queue::assertNotPushed(UpdateEventForumThreadJob::class);
    }

    public function test_deleted_deletes_forum_thread_when_xenforo_is_configured_and_thread_exists(): void
    {
        Queue::fake();

        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withForumThreadEnabled()
            ->withForumThreadId(321)
            ->create([
                Event::POST_ID => 654,
            ]);

        $subject = new EventObserver();

        $subject->deleted($event);

        Queue::assertPushed(DeleteEventForumThreadJob::class);
    }
}