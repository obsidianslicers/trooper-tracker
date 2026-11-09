<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Events\EventClosed;
use App\Events\EventPosted;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Listeners\ArchiveEventThread;
use TroopTracker\Plugins\Xenforo\Listeners\CreateEventThread;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class EventThreadsTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
        config([
            'plugins.xenforo.base_url' => 'https://forums.example.com',
            'plugins.xenforo.api_key' => 'abc',
        ]);
    }

    public function test_posting_an_event_creates_its_thread(): void
    {
        $organization = $this->createForumOrganization([OrganizationForum::EVENT_FORUM_ID => 42]);
        $creator = Trooper::factory()->create();
        $event = Event::factory()
            ->withOrganization($organization)
            ->withCreatedByTrooper($creator)
            ->create();

        $messages = Mockery::mock(ForumThreadMessageService::class);
        $messages->shouldReceive('buildThreadMessage')->once()->andReturn('[b]Thread[/b]');

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')
            ->once()
            ->with($creator->id)
            ->andReturn(9876);
        $xenforo->shouldReceive('create_thread')
            ->once()
            ->with(42, $event->name, '[b]Thread[/b]', 9876)
            ->andReturn([
                'status' => 201,
                'body' => ['thread' => ['thread_id' => 321, 'first_post_id' => 654]],
            ]);

        (new CreateEventThread($xenforo, $messages))->handle(new EventPosted($event));

        $thread = EventThread::forEvent($event->id);
        $this->assertSame(321, $thread->thread_id);
        $this->assertSame(654, $thread->post_id);
    }

    public function test_posting_skips_events_that_opted_out_or_already_have_a_thread(): void
    {
        $organization = $this->createForumOrganization([OrganizationForum::EVENT_FORUM_ID => 42]);
        $opted_out = $this->linkThread(
            Event::factory()->withOrganization($organization)->create(),
            null,
            null,
            false,
        );
        $linked = $this->linkThread(
            Event::factory()->withOrganization($organization)->create(),
            5,
            6,
        );

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('create_thread');
        $subject = new CreateEventThread($xenforo, Mockery::mock(ForumThreadMessageService::class));

        $subject->handle(new EventPosted($opted_out));
        $subject->handle(new EventPosted($linked));
    }

    public function test_closing_an_event_moves_its_thread_to_the_archive_forum(): void
    {
        $organization = $this->createForumOrganization([OrganizationForum::ARCHIVE_FORUM_ID => 77]);
        $event = $this->linkThread(
            Event::factory()->withOrganization($organization)->create(),
            321,
            654,
        );

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('move_thread')->once()->with(321, 77)->andReturn(['status' => 200]);

        (new ArchiveEventThread($xenforo))->handle(new EventClosed($event));
    }

    public function test_event_form_saves_the_thread_fields(): void
    {
        Queue::fake();
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::DRAFT])->create();

        $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => EventType::REGULAR->value,
            'status' => EventStatus::DRAFT->value,
            'organization_id' => $event->organization_id,
            'event_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'event_end' => now()->addDays(7)->addHours(4)->format('Y-m-d H:i:s'),
            'xenforo_create_thread' => 0,
            'xenforo_thread_id' => 321,
            'xenforo_post_id' => '',
        ])->assertRedirect();

        $thread = EventThread::forEvent($event->id);
        $this->assertFalse($thread->should_create_thread);
        $this->assertSame(321, $thread->thread_id);
        $this->assertNull($thread->post_id);
    }

    public function test_event_page_shows_forum_comments_and_post_button(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->linkThread(Event::factory()->create(), 321, 654);

        $this->mock(XenforoService::class, function (MockInterface $mock): void
        {
            $mock->shouldReceive('get_smilies')->andReturn([]);
            $mock->shouldReceive('get_thread_posts')
                ->once()
                ->with(321, 654, 50, 20)
                ->andReturn([['username' => 'TK-421', 'message' => 'See you there']]);
        });

        $response = $this->actingAs($trooper)->get(route('events.display', $event));

        $response->assertOk();
        $response->assertSee('Forum Comments');
        $response->assertSee('See you there');
        $response->assertSee('https://forums.example.com/posts/654/', false);
        $response->assertSee(route('events.forum-reply-htmx', $event), false);
    }
}
