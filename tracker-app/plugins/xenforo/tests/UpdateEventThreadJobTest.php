<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Jobs\UpdateEventThreadJob;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class UpdateEventThreadJobTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_serialization_preserves_target_event_id(): void
    {
        $subject = new UpdateEventThreadJob(11698);

        $restored = unserialize(serialize($subject));

        $this->assertSame('forum-thread-sync:event:11698', $restored->uniqueId());
    }

    public function test_handle_updates_only_the_targeted_event_for_single_event_sync(): void
    {
        $organization = Organization::factory()->create();
        $creator = Trooper::factory()->create();

        $target_event = $this->linkThread(Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->create([Event::EVENT_END => now()->subHours(6)]), 123, 456);

        $this->linkThread(Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->create(), 789, 987);

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
            ->with(123, (string) $target_event->name);
        $xenforo->shouldReceive('update_post')
            ->once()
            ->with(456, 'updated forum message', 42);

        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'test-key',
        ]);

        $subject = new UpdateEventThreadJob($target_event->id);

        $subject->handle($xenforo, $forumThreadMessageService);
    }

    public function test_handle_keeps_a_dated_title_in_step_with_the_event_date(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://xf.test',
            'plugins.xenforo.api_key' => 'test-key',
            'plugins.xenforo.date_in_thread_titles' => true,
        ]);

        //  the title is rebuilt from the event as it is now, so a moved date reaches the forum
        $event = $this->linkThread(Event::factory()->create([
            Event::NAME => 'Hospital Visit',
            Event::EVENT_START => '2027-03-21 10:00:00',
            Event::EVENT_END => '2027-03-21 12:00:00',
        ]), 123, 456);

        $messages = Mockery::mock(ForumThreadMessageService::class);
        $messages->shouldReceive('buildThreadMessage')->andReturn('updated forum message');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(null);
        $xenforo->shouldReceive('update_thread')->once()->with(123, '2027/03/21 Sun - Hospital Visit');
        $xenforo->shouldReceive('update_post')->once();

        (new UpdateEventThreadJob($event->id))->handle($xenforo, $messages);
    }
}
