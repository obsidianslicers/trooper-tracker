<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Forums\ForumThreadMessageService;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateEventForumThreadJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_serialization_preserves_target_event_id(): void
    {
        $subject = new UpdateEventForumThreadJob(11698);

        $restored = unserialize(serialize($subject));

        $this->assertSame('forum-thread-sync:event:11698', $restored->uniqueId());
    }

    public function test_handle_updates_only_the_targeted_event_for_single_event_sync(): void
    {
        $organization = Organization::factory()->create();
        $creator = Trooper::factory()->create();

        $target_event = Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->withForumThreadEnabled()
            ->withForumThreadId(123)
            ->create([
                Event::POST_ID => 456,
                Event::EVENT_END => now()->subHours(6),
            ]);

        Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->withForumThreadEnabled()
            ->withForumThreadId(789)
            ->create([
                Event::POST_ID => 987,
            ]);

        $forumThreadMessageService = Mockery::mock(ForumThreadMessageService::class);
        $forumThreadMessageService->shouldReceive('buildThreadMessage')
            ->once()
            ->with(Mockery::on(fn (Event $event): bool => $event->is($target_event)))
            ->andReturn('updated forum message');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->with($creator->id)
            ->andReturn(42);
        $xenforo->shouldReceive('update_thread')
            ->once()
            ->with(123, (string) $target_event->name, 42);
        $xenforo->shouldReceive('update_post')
            ->once()
            ->with(456, 'updated forum message', 42);

        $subject = new UpdateEventForumThreadJob($target_event->id);

        $subject->handle($xenforo, $forumThreadMessageService);
    }
}